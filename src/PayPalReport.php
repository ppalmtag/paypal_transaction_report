<?php

namespace PayPalReport;

/**
 * Description of PayPalTransactionReport
 *
 * Class for reading the PayPal Transaction Detail Report (TRR)
 *
 * Limitations:
 * 1. Multiple Account Management is not possible, section management is not
 * included.
 *
 * @author ppalmtag
 */
class PayPalTransactionReport {

    /* Constants */

    const REPORTING_WINDOW_TIMEZONES = [
        'A', // America/New York to America/Los Angeles
        'H', // America/Los Angeles to Asia/Hong Kong
        'R', // Asia/Hong Kong to Europe/London
        'X', // Europe/London to America/New York
    ];

    const MONEY_MOVEMENT = [
        'CR' => '+', // Credit - Guthaben = +
        'DR' => '-', // Debit - Soll = -
    ];

    const CSV_DELIMITER = ',';

    const TRANSACTION_SUCCESSFULLY_COMPLETED = 'S';
    const TRANSACTION_DENIED = 'D';
    const TRANSACTION_PENDING = 'P';
    const TRANSACTION_REVERSED = 'V';
    const TRANSACTION_PARTIALLY_REFUNDED = 'P';

    /* Variables */




    /* Data of the report */

    /**
     * RH
     * Report Header Data
     *
     * @var type
     */
    private $report_header = [
        'generation_date' => null,
        'reporting_window' => null,
        'account_id_type' => null,
        'report_version' => null,
    ];

    /**
     * FH
     * File Header Data
     * The sequence number of the file in the report
     *
     * @var type
     */
    private $file_header;

    /**
     * SH
     *
     * @var type
     */
    private $section_header = [
        'reporting_period_start_date' => null,
        'reporting_period_end_date' => null,
        'account_id_type' => null,
        'partner_account_id' => null,
    ];

    /**
     * CH
     * Section Body Data
     *
     * @var type
     */
    private $column_header;

    /**
     * SB
     * Section Body Data
     *
     * @var type
     */
    private $row_data = [];

    /**
     * SF
     * Section Footer Data
     *
     * @var type
     */
    private $section_footer;

    /**
     * SC
     * Section Record Count Data
     *
     * @var type
     */
    private $section_record_count;

    /**
     * RF
     * Report Footer Data
     *
     * @var type
     */
    private $report_footer;

    /**
     * RC
     * Report Record Count Data
     *
     * @var type
     */
    private $report_record_count;

    /**
     * FF
     * File Footer Data
     *
     * @var type
     */
    private $file_footer;

    /**
     * Reads a report and set the data of the object, throws exception on failure
     *
     * @param string $csv_data
     * @return PayPalTransactionReport
     * @throws Exception
     */
    public function readReport(string $csv_data): PayPalTransactionReport
    {
        // read file in array
        $lines = explode("\n", $csv_data);

        // parse lines put data in array
        foreach ($lines as $line) {
            // fix user comments
            $fixed_line = $this->fixUserComments($line);
            $data = str_getcsv($fixed_line, self::CSV_DELIMITER, '"', '"');

            // set the data
            if (!$this->setData($data)) {
                throw new Exception('Failed setting '.$data[0]);
            }
        }
        return $this;
    }


    /* ---------- Setters ---------- */

    /**
     * Function that selects the correct insertion function for the given data
     * array
     *
     * @param array $data
     * @return bool
     */
    private function setData(array $data): bool
    {
        $success = false;

        switch ($data[0]) {
            case 'RH':
                $success = $this->setReportHeaderData($data);
                break;
            case 'FH':
                $success = $this->setFileHeaderData($data);
                break;
            case 'SH':
                $success = $this->setSectionHeaderData($data);
                break;
            case 'CH':
                $success = $this->setColumnHeaderData($data);
                break;
            case 'SB':
                $success = $this->setRowData($data);
                break;
            case 'SF':
                $success = $this->setSectionFooterData($data);
                break;
            case 'SC':
                $success = $this->setSectionRecordCountData($data);
                break;
            case 'RF':
                $success = $this->setReportFooterData($data);
                break;
            case 'RC':
                $success = $this->setReportRecordCountData($data);
                break;
            case 'FF':
                $success = $this->setFileFooterData($data);
                break;
            default:
                $success = false;
                break;
        }

        return $success;
    }

    /**
     * Set the report header information
     *
     * @param array $data
     * @return bool
     */
    private function setReportHeaderData(array $data): bool
    {
        if (count($data) >= 5) {
            $this->report_header['generation_date'] = $data[1];
            $this->report_header['reporting_window'] = $data[2];
            $this->report_header['account_id_type'] = $data[3];
            $this->report_header['report_version'] = $data[4];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the file header data, represents the number of file the report
     * is split into
     *
     * @param array $data
     * @return bool
     */
    private function setFileHeaderData(array $data): bool
    {
        if (count($data) >= 1) {
            $this->file_header = (int)$data[1];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the column header data, represents the data lines descriptions
     *
     * @param array $data
     * @return bool
     */
    private function setColumnHeaderData(array $data): bool
    {
        if (count($data) >= 68) {
            array_shift($data);
            $this->column_header = array_map('trim', $data);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the row data, represents the data lines
     *
     * @param array $data
     * @return bool
     */
    private function setRowData(array $data): bool
    {
        array_shift($data);
        if (count($data) >= 67 && count($data) === count($this->column_header)) {

            $this->row_data[] = array_combine($this->column_header, array_map('trim', $data));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the section footer data, represents the number of file the report
     * is split into
     *
     * @param array $data
     * @return bool
     */
    private function setSectionFooterData(array $data): bool
    {
        if (count($data) >= 1) {
            $this->section_footer = (int)$data[1];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the section record count data, represents the number of file the report
     * is split into
     *
     * @param array $data
     * @return bool
     */
    private function setSectionRecordCountData(array $data): bool
    {
        if (count($data) >= 1) {
            $this->section_record_count = (int)$data[1];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the report footer data, represents the number of body data rows in the report
     *
     * @param array $data
     * @return bool
     */
    private function setReportFooterData(array $data): bool
    {
        if (count($data) >= 1) {
            $this->report_footer = (int)$data[1];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the report count data, represents the number of body data rows in the report
     *
     * @param array $data
     * @return bool
     */
    private function setReportRecordCountData(array $data): bool
    {
        if (count($data) >= 1) {
            $this->report_record_count = (int)$data[1];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the file footer data, represents the number of body data rows in the file
     *
     * @param array $data
     * @return bool
     */
    private function setFileFooterData(array $data): bool
    {
        if (count($data) >= 1) {
            $this->file_footer = (int)$data[1];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the section header data, showing the
     *
     * @param array $data
     * @return bool
     */
    private function setSectionHeaderData(array $data): bool
    {
        if (count($data) >= 5) {
            $this->section_header['reporting_period_start_date'] = $data[1];
            $this->section_header['reporting_period_end_date'] = $data[2];
            $this->section_header['account_id_type'] = $data[3];
            $this->section_header['partner_account_id'] = $data[4];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Removes all data from the object
     *
     * @return PayPalTransactionReport
     */
    public function flush(): PayPalTransactionReport
    {
        $this->report_header['generation_date'] = null;
        $this->report_header['reporting_window'] = null;
        $this->report_header['account_id_type'] = null;
        $this->report_header['report_version'] = null;
        $this->file_header = null;
        $this->column_header = null;
        $this->row_data = [];
        $this->section_footer = null;
        $this->section_record_count = null;
        $this->report_footer = null;
        $this->report_record_count = null;
        $this->file_footer = null;
        $this->section_header['reporting_period_start_date'] = null;
        $this->section_header['reporting_period_end_date'] = null;
        $this->section_header['account_id_type'] = null;
        $this->section_header['partner_account_id'] = null;

        return $this;
    }


    /* ---------- Utility ---------- */

    /**
     * Escapes unescaped double quotes in data from the given line
     *
     * @param string $line
     * @return string
     */
    private function fixUserComments(string $line): string
    {
        // only modify data rows, ignore others
        if (substr($line, 0, 4) !== '"SB"') {
            return $line;
        }

        $parts = explode(self::CSV_DELIMITER, $line);
        $new_parts = [];
        foreach ($parts as $part) {
            $new_parts[] = preg_replace('/(?!^.?)"(?!.{0}$)/', '""', $part);
        }

        return implode(self::CSV_DELIMITER, $new_parts);
    }


    /* ---------- Getters ---------- */


    /**
     * Returns \DateTime object in correct timezone
     *
     * @param string $datestring
     * @return string
     */
    public function getDateTime(string $datestring): \DateTime
    {
        $dt = new \DateTime($datestring);
        $dt->setTimezone(new \DateTimeZone('Europe/Berlin'));
        return $dt;
    }

    /**
     * Get DateTime object of report generation date
     *
     * @return \DateTime
     */
    public function getReportGenerationDate(): \DateTime
    {
        return $this->getDateTime($this->report_header['generation_date']);
    }

    /**
     * Get DateTime object of section period start date
     *
     * @return \DateTime
     */
    public function getReportSectionStartDate(): \DateTime
    {
        return $this->getDateTime($this->section_header['reporting_period_start_date']);
    }

    /**
     * Returns data rows
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->row_data;
    }

    /**
     * Only the last file has a report footer and a report record count record,
     * so if they are not set there are still files to read
     *
     * @return bool
     */
    public function hasNext(): bool
    {
        return empty($this->report_footer) && empty($this->report_record_count);
    }

    /**
     * Get number of data rows that were inserted into the object
     *
     * @return int
     */
    public function getRecordCount(): int
    {
        return count($this->row_data);
    }

    /**
     * Get number of records that should be in the report
     *
     * @return int
     */
    public function getReportRecordCount(): int
    {
        return $this->report_record_count;
    }

}
