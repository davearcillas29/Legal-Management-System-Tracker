<?php
if (!function_exists('word_or_digit_to_number')) {
    function word_or_digit_to_number($str) {
        $str = trim(strtolower($str), " ()[].,");
        $wordMap = [
            'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5,
            'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10,
            'eleven' => 11, 'twelve' => 12, 'eighteen' => 18, 'twenty' => 20,
            'twenty-four' => 24, 'thirty' => 30, 'thirty-six' => 36, 'forty-eight' => 48,
            'sixty' => 60
        ];
        if (isset($wordMap[$str])) return $wordMap[$str];
        if (is_numeric($str)) return intval($str);
        return null;
    }
}

if (!function_exists('parse_date_to_sql')) {
    function parse_date_to_sql($str) {
        if (!$str) return null;
        $str = trim($str, " .\t\n\r\0\x0B,;");
        
        // Discard non-dates / placeholders
        if (preg_match('/^(not specified|none specified|none|n\/?a|unknown|pending|to be determined|tbd|nil)/i', $str)) {
            return null;
        }

        $monthNames = 'January|February|March|April|May|June|July|August|September|October|November|December|Jan|Feb|Mar|Apr|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec';

        // 1. Month Day, Year (e.g., "August 29, 2019", "September 18, 2026")
        if (preg_match('/(' . $monthNames . ')\s+(\d{1,2})(?:st|nd|rd|th)?,?\s+(\d{4})/i', $str, $m)) {
            $ts = strtotime($m[1] . ' ' . $m[2] . ' ' . $m[3]);
            if ($ts) return date('Y-m-d', $ts);
        }

        // 2. Day Month Year (e.g., "29 August 2019", "15th of January, 2021")
        if (preg_match('/(\d{1,2})(?:st|nd|rd|th)?(?:\s+of)?\s+(' . $monthNames . '),?\s+(\d{4})/i', $str, $m)) {
            $ts = strtotime($m[1] . ' ' . $m[2] . ' ' . $m[3]);
            if ($ts) return date('Y-m-d', $ts);
        }

        // 3. ISO format YYYY-MM-DD
        if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $str, $m)) {
            $ts = strtotime($m[0]);
            if ($ts) return date('Y-m-d', $ts);
        }

        // 4. MM/DD/YYYY or DD/MM/YYYY
        if (preg_match('/\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/', $str, $m)) {
            $ts = strtotime($m[0]);
            if ($ts) return date('Y-m-d', $ts);
        }

        // 5. Month and Year only (e.g. "November 2025", "July 2020")
        if (preg_match('/(' . $monthNames . '),?\s+(\d{4})/i', $str, $m)) {
            $ts = strtotime('1 ' . $m[1] . ' ' . $m[2]);
            if ($ts) return date('Y-m-d', $ts);
        }

        // 6. School Year / End of Year: "End of School Year 2028" -> 2028-06-30
        if (preg_match('/(?:end of|sy|school year)\s*.*?(\d{4})/i', $str, $m)) {
            return $m[1] . '-06-30';
        }

        // 7. General strtotime fallback (stripping common narrative words)
        $cleanStr = preg_replace('/[()]|\b(?:upon execution|upon signing|approximately|projected|tentative|around)\b/i', '', $str);
        $ts = strtotime(trim($cleanStr));
        if ($ts && $ts > 0 && date('Y', $ts) > 1990 && date('Y', $ts) < 2100) {
            return date('Y-m-d', $ts);
        }

        return null;
    }
}

if (!function_exists('extract_text_from_pdf_simple')) {
    function extract_text_from_pdf_simple($pdfPath) {
        if (!$pdfPath) return '';
        $resolvedPath = $pdfPath;
        if (!file_exists($resolvedPath)) {
            if (file_exists(__DIR__ . '/../' . ltrim($pdfPath, '/\\'))) {
                $resolvedPath = __DIR__ . '/../' . ltrim($pdfPath, '/\\');
            } elseif (file_exists(__DIR__ . '/../../' . ltrim($pdfPath, '/\\'))) {
                $resolvedPath = __DIR__ . '/../../' . ltrim($pdfPath, '/\\');
            }
        }
        if (!file_exists($resolvedPath)) return '';
        $content = @file_get_contents($resolvedPath);
        if (!$content) return '';

        $text = '';
        if (preg_match_all('#stream[\r\n]+(.*?)[\r\n]+endstream#s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                $uncompressed = @gzuncompress($stream);
                if ($uncompressed !== false) {
                    if (preg_match_all('#\((.*?)\)\s*Tj#s', $uncompressed, $textMatches)) {
                        $text .= implode(' ', $textMatches[1]) . "\n";
                    } elseif (preg_match_all('#\[(.*?)\]\s*TJ#s', $uncompressed, $tjMatches)) {
                        foreach ($tjMatches[1] as $tj) {
                            if (preg_match_all('#\((.*?)\)#s', $tj, $subMatches)) {
                                $text .= implode('', $subMatches[1]) . " ";
                            }
                        }
                        $text .= "\n";
                    }
                }
            }
        }
        return trim($text);
    }
}

if (!function_exists('extract_moa_details')) {
    function extract_moa_details($summaryHtml, $postData = [], $pdfPath = null) {
        $extracted = [
            'moa_title'            => null,
            'partner_organization' => null,
            'signatory'            => null,
            'date_issued'          => null,
            'effective_date'       => null,
            'date_signed'          => null,
            'expiration_date'      => null,
            'key_dates'            => null,
            'raw_effective'        => null,
            'raw_issued'           => null,
            'raw_signed'           => null,
            'raw_expiration'       => null,
            'computed_status'      => 'DRAFT',
        ];

        // Direct POST values take top priority
        if (!empty($postData['moa_title']))            $extracted['moa_title'] = trim($postData['moa_title']);
        if (!empty($postData['partner_organization'])) $extracted['partner_organization'] = trim($postData['partner_organization']);
        if (!empty($postData['signatory']))            $extracted['signatory'] = trim($postData['signatory']);
        if (!empty($postData['date_issued']))          $extracted['date_issued'] = parse_date_to_sql($postData['date_issued']);
        if (!empty($postData['effective_date']))       $extracted['effective_date'] = parse_date_to_sql($postData['effective_date']);
        if (!empty($postData['date_signed']))          $extracted['date_signed'] = parse_date_to_sql($postData['date_signed']);
        if (!empty($postData['expiration_date']))      $extracted['expiration_date'] = parse_date_to_sql($postData['expiration_date']);
        if (!empty($postData['key_dates']))            $extracted['key_dates'] = trim($postData['key_dates']);

        // Gather text sources: Summary HTML + PDF content if available
        $textSources = [];
        if (!empty($summaryHtml)) {
            $textSources[] = $summaryHtml;
        }
        if (!empty($pdfPath) && file_exists($pdfPath)) {
            $pdfText = extract_text_from_pdf_simple($pdfPath);
            if (!empty($pdfText)) {
                $textSources[] = $pdfText;
            }
        }

        foreach ($textSources as $sourceText) {
            // Pattern: HTML list items or markdown bold key-value pairs
            $pattern = '/(?:<li>\s*<strong>|\*\*|\b)([A-Za-z0-9\s\/\-\&]+?)(?::<\/strong>|\*\*:|:)\s*(.*?)(?:<\/li>|\n|$)/is';
            if (preg_match_all($pattern, $sourceText, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $label = strtolower(trim(strip_tags($match[1])));
                    $val = trim(strip_tags($match[2]));
                    $val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    
                    if (empty($val)) continue;

                    // Subject / Title
                    if (strpos($label, 'subject') !== false || strpos($label, 'matter') !== false) {
                        if (!$extracted['moa_title']) $extracted['moa_title'] = $val;
                    } 
                    // Partner / Parties
                    elseif (strpos($label, 'key parties') !== false || strpos($label, 'parties') !== false || strpos($label, 'partner') !== false) {
                        if (!$extracted['partner_organization']) $extracted['partner_organization'] = $val;
                    } 
                    // Signatory
                    elseif (strpos($label, 'signatory') !== false || strpos($label, 'signed by') !== false) {
                        if (!$extracted['signatory']) $extracted['signatory'] = $val;
                    } 
                    // Date Issued / Signed combined
                    elseif (strpos($label, 'date issued / signed') !== false || strpos($label, 'date issued/signed') !== false || strpos($label, 'issued / signed') !== false) {
                        $extracted['raw_issued'] = $val;
                        $extracted['raw_signed'] = $val;
                        $parsed = parse_date_to_sql($val);
                        if (!$extracted['date_issued'] && $parsed) $extracted['date_issued'] = $parsed;
                        if (!$extracted['date_signed'] && $parsed) $extracted['date_signed'] = $parsed;
                    } 
                    // Date Issued
                    elseif (strpos($label, 'date issued') !== false || strpos($label, 'date of issue') !== false) {
                        $extracted['raw_issued'] = $val;
                        $parsed = parse_date_to_sql($val);
                        if (!$extracted['date_issued'] && $parsed) $extracted['date_issued'] = $parsed;
                    } 
                    // Date Signed
                    elseif (strpos($label, 'date signed') !== false || strpos($label, 'date of signing') !== false || strpos($label, 'execution date') !== false) {
                        $extracted['raw_signed'] = $val;
                        $parsed = parse_date_to_sql($val);
                        if (!$extracted['date_signed'] && $parsed) $extracted['date_signed'] = $parsed;
                    } 
                    // Effective Date
                    elseif (strpos($label, 'effective date') !== false || strpos($label, 'commencement date') !== false || strpos($label, 'start date') !== false) {
                        $extracted['raw_effective'] = $val;
                        $parsed = parse_date_to_sql($val);
                        if (!$extracted['effective_date'] && $parsed) $extracted['effective_date'] = $parsed;
                    } 
                    // Expiration Date
                    elseif (strpos($label, 'expiration date') !== false || strpos($label, 'expiry date') !== false || strpos($label, 'termination date') !== false || strpos($label, 'valid until') !== false) {
                        $extracted['raw_expiration'] = $val;
                        $parsed = parse_date_to_sql($val);
                        if (!$extracted['expiration_date'] && $parsed) $extracted['expiration_date'] = $parsed;
                    } 
                    // Key Dates / Deadlines
                    elseif (strpos($label, 'key dates') !== false || strpos($label, 'deadlines') !== false || strpos($label, 'milestone') !== false) {
                        if (!$extracted['key_dates']) $extracted['key_dates'] = $val;
                    }
                }
            }
        }

        // Cross-date inferences
        // 1. If date_issued or date_signed is known, sync them if one is null
        if (!$extracted['date_issued'] && $extracted['date_signed']) {
            $extracted['date_issued'] = $extracted['date_signed'];
        }
        if (!$extracted['date_signed'] && $extracted['date_issued']) {
            $extracted['date_signed'] = $extracted['date_issued'];
        }

        // 2. If effective date says "Upon execution" or "Upon signing" and date_signed is known
        if (!$extracted['effective_date'] && $extracted['date_signed']) {
            if ($extracted['raw_effective'] && preg_match('/upon execution|upon signing/i', $extracted['raw_effective'])) {
                $extracted['effective_date'] = $extracted['date_signed'];
            }
        }

        // 3. If expiration date was expressed as a duration (e.g. "Eleven (11) months after the signing")
        if (!$extracted['expiration_date'] && !empty($extracted['raw_expiration'])) {
            $baseDate = $extracted['effective_date'] ?: $extracted['date_signed'] ?: $extracted['date_issued'];
            if ($baseDate) {
                // Check months
                if (preg_match('/(one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|\d+|\(\d+\))\s*(?:\(\d+\)\s*)?(?:month|mos)/i', $extracted['raw_expiration'], $dm)) {
                    $months = word_or_digit_to_number($dm[1]);
                    if ($months) {
                        $extracted['expiration_date'] = date('Y-m-d', strtotime("+$months months", strtotime($baseDate)));
                    }
                }
                // Check years
                elseif (preg_match('/(one|two|three|four|five|\d+|\(\d+\))\s*(?:\(\d+\)\s*)?(?:year|yr)/i', $extracted['raw_expiration'], $dy)) {
                    $years = word_or_digit_to_number($dy[1]);
                    if ($years) {
                        $extracted['expiration_date'] = date('Y-m-d', strtotime("+$years years", strtotime($baseDate)));
                    }
                }
                // Check days
                elseif (preg_match('/(\d+)\s*(?:day|days)/i', $extracted['raw_expiration'], $dd)) {
                    $days = intval($dd[1]);
                    if ($days) {
                        $extracted['expiration_date'] = date('Y-m-d', strtotime("+$days days", strtotime($baseDate)));
                    }
                }
            }
        }

        // 4. Compute status based on expiration date
        if (!empty($extracted['expiration_date'])) {
            $today = strtotime(date('Y-m-d'));
            $exp = strtotime($extracted['expiration_date']);
            $diffDays = ($exp - $today) / 86400;
            if ($diffDays < 0) {
                $extracted['computed_status'] = 'EXPIRED';
            } elseif ($diffDays <= 30) {
                $extracted['computed_status'] = 'EXPIRING';
            } else {
                $extracted['computed_status'] = 'ACTIVE';
            }
        }

        return $extracted;
    }
}

if (!function_exists('ensure_moa_database_schema')) {
    function ensure_moa_database_schema($conn) {
        $requiredEmailColumns = [
            'moa_title'            => 'VARCHAR(255) NULL',
            'partner_organization' => 'VARCHAR(255) NULL',
            'signatory'            => 'VARCHAR(255) NULL',
            'date_issued'          => 'DATE NULL',
            'effective_date'       => 'DATE NULL',
            'date_signed'          => 'DATE NULL',
            'expiration_date'      => 'DATE NULL',
            'key_dates'            => 'TEXT NULL'
        ];

        foreach ($requiredEmailColumns as $col => $def) {
            $chk = $conn->query("SHOW COLUMNS FROM `incoming_email_audits` LIKE '$col'");
            if ($chk && $chk->num_rows == 0) {
                $conn->query("ALTER TABLE `incoming_email_audits` ADD COLUMN `$col` $def");
            }
        }

        $chkIntake = $conn->query("SHOW COLUMNS FROM `audits` LIKE 'key_dates'");
        if ($chkIntake && $chkIntake->num_rows == 0) {
            $conn->query("ALTER TABLE `audits` ADD COLUMN `key_dates` TEXT NULL");
        }
    }
}

if (!function_exists('backfill_moa_email_records')) {
    function backfill_moa_email_records($conn) {
        $sql = "SELECT id, subject, summary_html, original_pdf_path FROM incoming_email_audits WHERE (effective_date IS NULL AND expiration_date IS NULL) OR key_dates IS NULL";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            $updateStmt = $conn->prepare("
                UPDATE incoming_email_audits 
                SET moa_title = COALESCE(NULLIF(moa_title, ''), ?),
                    partner_organization = COALESCE(NULLIF(partner_organization, ''), ?),
                    signatory = COALESCE(NULLIF(signatory, ''), ?),
                    date_issued = COALESCE(date_issued, ?),
                    effective_date = COALESCE(effective_date, ?),
                    date_signed = COALESCE(date_signed, ?),
                    expiration_date = COALESCE(expiration_date, ?),
                    key_dates = COALESCE(NULLIF(key_dates, ''), ?)
                WHERE id = ?
            ");

            while ($row = $res->fetch_assoc()) {
                $extracted = extract_moa_details($row['summary_html'], [], $row['original_pdf_path']);
                
                $title = $extracted['moa_title'] ?: $row['subject'];
                $partner = $extracted['partner_organization'];
                $signatory = $extracted['signatory'];
                $dateIssued = $extracted['date_issued'];
                $effectiveDate = $extracted['effective_date'];
                $dateSigned = $extracted['date_signed'];
                $expirationDate = $extracted['expiration_date'];
                $keyDates = $extracted['key_dates'];
                $id = (int)$row['id'];

                $updateStmt->bind_param(
                    "ssssssssi",
                    $title,
                    $partner,
                    $signatory,
                    $dateIssued,
                    $effectiveDate,
                    $dateSigned,
                    $expirationDate,
                    $keyDates,
                    $id
                );
                $updateStmt->execute();
            }
            $updateStmt->close();
        }
    }
}