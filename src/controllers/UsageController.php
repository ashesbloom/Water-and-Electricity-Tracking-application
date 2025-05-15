<?php
// src/controllers/UsageController.php

// Ensure the Database class is available. Adjust path if necessary.
// require_once __DIR__ . '/../../config/database.php'; // Assuming database.php defines the Database class


class UsageController {
    private $db; // Instance of the Database class
    private $conn; // PDO connection object

    /**
     * Constructor: Establishes database connection.
     */
    public function __construct() {
         // This assumes your Database class and connection logic are correctly set up
         // If database.php isn't automatically loaded, you might need to require it here or ensure it's loaded globally.
        global $pdo; // Assuming $pdo is your global connection variable from database.php
        if (isset($pdo)) {
             $this->conn = $pdo;
        } else {
            // Fallback or alternative connection method if $pdo isn't global
            try {
                 // This path might need adjustment depending on your project structure
                 require_once __DIR__ . '/../../config/database.php';
                 $database = new Database(); // Assumes Database class exists
                 $this->conn = $database->getConnection();
            } catch (Exception $e) {
                 error_log("UsageController: Failed to establish database connection - " . $e->getMessage());
                 $this->conn = null; // Ensure conn is null on failure
            }
        }

        if ($this->conn === null) {
            error_log("UsageController: Database connection is null after attempting initialization.");
            // Consider throwing an exception or handling this more robustly
        }
    }

    // --- addUsageRecord method ---
    /**
     * Adds a new usage record to the database.
     *
     * @param int $userId The ID of the user.
     * @param string $type 'electricity' or 'water'.
     * @param float $amount The usage amount.
     * @param string $dateTime The date and time of the reading (e.g., 'Y-m-d H:i:s' or 'Y-m-d\TH:i').
     * @param string|null $notes Optional notes for the record.
     * @return array Associative array with 'success' (bool) and 'message' (string).
     */
    public function addUsageRecord($userId, $type, $amount, $dateTime, $notes = null) {
        if ($this->conn === null) { return ['success' => false, 'message' => 'Database connection error. Cannot add record.']; }
        if (empty($userId) || !is_numeric($userId)) { return ['success' => false, 'message' => 'Invalid user ID.']; }
        if ($type !== 'electricity' && $type !== 'water') { return ['success' => false, 'message' => 'Invalid usage type specified.']; }
        if (!is_numeric($amount) || $amount < 0) { return ['success' => false, 'message' => 'Invalid usage amount. Must be a non-negative number.']; }
        if (empty($dateTime)) { return ['success' => false, 'message' => 'Date and time cannot be empty.']; }

        try {
            // Attempt to create DateTime object and format for DB
            $dateObject = new DateTime($dateTime);
            $dbDateTime = $dateObject->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            error_log("DateTime Error in addUsageRecord: " . $e->getMessage() . " for input: " . $dateTime);
            return ['success' => false, 'message' => 'Invalid date/time format. Please use the format YYYY-MM-DDTHH:MM.'];
        }

        // Use backticks for reserved keywords if necessary, adjust table/column names
        $sql = "INSERT INTO `usage_records` (`user_id`, `usage_type`, `usage_amount`, `usage_date`, `notes`) VALUES (:user_id, :usage_type, :usage_amount, :usage_date, :notes)";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':usage_type', $type, PDO::PARAM_STR);
            $stmt->bindParam(':usage_amount', $amount, PDO::PARAM_STR); // Bind amount as string for broader compatibility (PDO handles conversion)
            $stmt->bindParam(':usage_date', $dbDateTime, PDO::PARAM_STR);

            // Bind notes, handling null correctly
            if ($notes === null || $notes === '') {
                $stmt->bindValue(':notes', null, PDO::PARAM_NULL); // Use bindValue for explicit NULL
            } else {
                $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
            }

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Usage record added successfully.'];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Database Error (Execute Failed): SQLSTATE[{$errorInfo[0]}] - Code[{$errorInfo[1]}] - Message[{$errorInfo[2]}]");
                return ['success' => false, 'message' => 'Failed to execute database statement.'];
            }
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            error_log("Database PDOException during INSERT: Code[{$errorCode}] - Message[{$errorMessage}] - UserID[{$userId}], Type[{$type}], Amount[{$amount}], Date[{$dbDateTime}], Notes[{$notes}]");
            return ['success' => false, 'message' => 'A database error occurred while adding the record. Please check server logs for details.'];
        }
    }

    // --- getTodayUsage method ---
    /**
     * Gets hourly aggregated usage for a specific type for the current date.
     *
     * @param int $userId The user's ID.
     * @param string $usageType 'electricity' or 'water'.
     * @return array Array of ['hour' => int, 'usage' => float] or empty array on failure.
     */
     public function getTodayUsage($userId, $usageType) {
        if ($this->conn === null) { error_log("getTodayUsage: No database connection."); return []; }
        if (empty($userId) || ($usageType !== 'electricity' && $usageType !== 'water')) { error_log("getTodayUsage: Invalid parameters. UserID: $userId, Type: $usageType"); return []; }

        // Use backticks for reserved keywords if necessary, adjust table/column names
        $sql = "SELECT HOUR(`usage_date`) as `hour`, SUM(`usage_amount`) as `usage`
                FROM `usage_records`
                WHERE `user_id` = :user_id
                  AND `usage_type` = :usage_type
                  AND DATE(`usage_date`) = CURDATE()
                GROUP BY HOUR(`usage_date`)
                ORDER BY `hour` ASC";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':usage_type', $usageType, PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Ensure correct types for JavaScript
            foreach ($results as &$row) {
                $row['usage'] = floatval($row['usage']);
                $row['hour'] = intval($row['hour']);
            }
            return $results;
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            error_log("Database PDOException during SELECT (getTodayUsage): Code[{$errorCode}] - Message[{$errorMessage}] - UserID[{$userId}], Type[{$usageType}]");
            return [];
        }
    }

    // --- Get Total Usage for a Specific Date ---
    /**
     * Calculates the total usage for a specific type on a given date.
     *
     * @param int $userId The user's ID.
     * @param string $usageType 'electricity' or 'water'.
     * @param string $date The date in 'Y-m-d' format.
     * @return float The total usage amount, or 0.0 on failure.
     */
    public function getUsageTotalForDate($userId, $usageType, $date) {
        error_log("[getUsageTotalForDate] Called with UserID: {$userId}, Type: {$usageType}, Date: {$date}");
        if ($this->conn === null) { error_log("[getUsageTotalForDate] No database connection."); return 0.0; }
        if (empty($userId) || ($usageType !== 'electricity' && $usageType !== 'water') || empty($date)) { error_log("[getUsageTotalForDate] Invalid parameters."); return 0.0; }

        try {
            $dateObj = new DateTime($date);
            $formattedDate = $dateObj->format('Y-m-d');
        } catch (Exception $e) {
            error_log("[getUsageTotalForDate] Invalid date format provided: $date");
            return 0.0;
        }

        // Use backticks for reserved keywords if necessary, adjust table/column names
        $sql = "SELECT SUM(`usage_amount`) as total_usage
                FROM `usage_records`
                WHERE `user_id` = :user_id
                  AND `usage_type` = :usage_type
                  AND DATE(`usage_date`) = :usage_date";
        error_log("[getUsageTotalForDate] SQL: {$sql}");
        error_log("[getUsageTotalForDate] Params - user_id: {$userId}, usage_type: {$usageType}, usage_date: {$formattedDate}");

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':usage_type', $usageType, PDO::PARAM_STR);
            $stmt->bindParam(':usage_date', $formattedDate, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("[getUsageTotalForDate] Raw DB Result: " . print_r($result, true));
            $total = ($result && $result['total_usage'] !== null) ? floatval($result['total_usage']) : 0.0;
            error_log("[getUsageTotalForDate] Calculated Total: {$total}");
            return $total;
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            error_log("[getUsageTotalForDate] Database PDOException during SELECT: Code[{$errorCode}] - Message[{$errorMessage}] - UserID[{$userId}], Type[{$usageType}], Date[{$formattedDate}]");
            return 0.0;
        }
    }

    // --- getDailyUsageForPeriod method ---
    /**
     * Gets daily aggregated usage for a specific type over a date range.
     *
     * @param int $userId The user's ID.
     * @param string $usageType 'electricity' or 'water'.
     * @param string $startDate Start date ('Y-m-d').
     * @param string $endDate End date ('Y-m-d').
     * @return array Array of ['date' => string, 'usage' => float] or empty array on failure.
     */
    public function getDailyUsageForPeriod($userId, $usageType, $startDate, $endDate) {
        if ($this->conn === null) { error_log("getDailyUsageForPeriod: No database connection."); return []; }
        if (empty($userId) || ($usageType !== 'electricity' && $usageType !== 'water') || empty($startDate) || empty($endDate)) { error_log("getDailyUsageForPeriod: Invalid parameters."); return []; }

        try {
            $startDateObj = new DateTime($startDate);
            $endDateObj = new DateTime($endDate);
            $formattedStartDate = $startDateObj->format('Y-m-d');
            $formattedEndDate = $endDateObj->format('Y-m-d');
        } catch (Exception $e) {
            error_log("getDailyUsageForPeriod: Invalid date format provided.");
            return [];
        }

        // Use backticks for reserved keywords if necessary, adjust table/column names
        $sql = "SELECT DATE(`usage_date`) as `date`, SUM(`usage_amount`) as `usage`
                FROM `usage_records`
                WHERE `user_id` = :user_id
                  AND `usage_type` = :usage_type
                  AND DATE(`usage_date`) BETWEEN :start_date AND :end_date
                GROUP BY DATE(`usage_date`)
                ORDER BY `date` ASC";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':usage_type', $usageType, PDO::PARAM_STR);
            $stmt->bindParam(':start_date', $formattedStartDate, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $formattedEndDate, PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Ensure correct type for JavaScript
            foreach ($results as &$row) {
                $row['usage'] = floatval($row['usage']);
            }
            return $results;
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            error_log("Database PDOException during SELECT (getDailyUsageForPeriod): Code[{$errorCode}] - Message[{$errorMessage}] - UserID[{$userId}], Type[{$usageType}]");
            return [];
        }
    }

    // --- getUsageNotes method ---
    /**
     * Retrieves usage records that have non-empty notes within a date range.
     *
     * @param int $userId The user's ID.
     * @param string $usageType 'electricity' or 'water'.
     * @param string $startDate Start date ('Y-m-d').
     * @param string $endDate End date ('Y-m-d').
     * @return array Array of records with notes or empty array on failure.
     */
    public function getUsageNotes($userId, $usageType, $startDate, $endDate) {
        if ($this->conn === null) { error_log("getUsageNotes: No database connection."); return []; }
        if (empty($userId) || ($usageType !== 'electricity' && $usageType !== 'water') || empty($startDate) || empty($endDate)) { error_log("getUsageNotes: Invalid parameters."); return []; }

        // Use backticks for reserved keywords if necessary, adjust table/column names
        $sql = "SELECT `record_id`, `usage_date`, `notes`, `usage_amount`, `usage_type`
                FROM `usage_records`
                WHERE `user_id` = :user_id
                  AND `usage_type` = :usage_type
                  AND `notes` IS NOT NULL AND `notes` != ''
                  AND DATE(`usage_date`) BETWEEN :start_date AND :end_date
                ORDER BY `usage_date` DESC";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':usage_type', $usageType, PDO::PARAM_STR);
            $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            error_log("Database PDOException during SELECT (getUsageNotes): Code[{$errorCode}] - Message[{$errorMessage}] - UserID[{$userId}], Type[{$usageType}]");
            return [];
        }
    }

    public function getPeakUsageForPeriod(int $userId, ?string $usageType, string $startDate, string $endDate): ?array {
        // Ensure database connection is available
        if ($this->conn === null) {
            error_log("getPeakUsageForPeriod: Database connection is not available.");
            throw new Exception("Database connection is not available.");
        }
       try {
           // SQL to find the single record with the highest usage amount within filters
           $sql = "SELECT `usage_amount`, `usage_type`, DATE(`usage_date`) as usage_date, HOUR(`usage_date`) as usage_hour
                   FROM `usage_records`
                   WHERE `user_id` = :user_id
                   AND `usage_date` >= :start_datetime AND `usage_date` <= :end_datetime";

           // Prepare parameters array for binding
           $params = [':user_id' => $userId];
           // Convert dates to full datetime for comparison
           $startDateTime = (new DateTime($startDate))->format('Y-m-d 00:00:00');
           $endDateTime = (new DateTime($endDate))->format('Y-m-d 23:59:59');
           $params[':start_datetime'] = $startDateTime;
           $params[':end_datetime'] = $endDateTime;

           // Add usage type filter if specified
           if ($usageType !== null && ($usageType === 'electricity' || $usageType === 'water')) {
               $sql .= " AND `usage_type` = :usage_type";
               $params[':usage_type'] = $usageType;
           }

           // Order by usage amount descending and limit to 1 result
           $sql .= " ORDER BY `usage_amount` DESC LIMIT 1";

           $stmt = $this->conn->prepare($sql);
           $stmt->execute($params); // Execute with parameters array
           $result = $stmt->fetch(PDO::FETCH_ASSOC);

           if ($result) {
               // Return formatted data if a peak record was found
               return [
                   'peak_date' => $result['usage_date'],
                   'peak_hour' => (int)$result['usage_hour'], // Ensure integer type
                   'peak_usage' => (float)$result['usage_amount'], // Ensure float type
                   'usage_type' => $result['usage_type'] ?? $usageType ?? 'combined' // Report the type found or queried
               ];
           } else {
               // No records found matching the criteria
               return null;
           }

       } catch (PDOException $e) {
           // Log database errors
           error_log("Database Error in getPeakUsageForPeriod: " . $e->getMessage());
           // Re-throw the exception to be handled by the calling API script
           throw new Exception("Failed to retrieve peak usage data due to a database error.");
       } catch (Exception $e) { // Catch potential DateTime errors
            error_log("Date Error in getPeakUsageForPeriod: Start=$startDate, End=$endDate - " . $e->getMessage());
            throw new Exception("Invalid date format provided for peak usage calculation.");
       }
   }

    /**
     * Searches usage records for specific keywords within the notes column.
     * @param int $userId User ID.
     * @param string $keywords Keywords to search for (space-separated).
     * @param string|null $usageType Optional filter by 'electricity' or 'water'.
     * @param string|null $startDate Optional start date ('Y-m-d').
     * @param string|null $endDate Optional end date ('Y-m-d').
     * @return array Array of matching records.
     */
    public function searchByNotes(int $userId, string $keywords, ?string $usageType, ?string $startDate, ?string $endDate): array {
        if ($this->conn === null) {
           error_log("searchByNotes: Database connection is not available.");
           throw new Exception("Database connection is not available.");
        }

        // Split keywords by space and filter out empty strings
        $keywordList = array_filter(explode(' ', trim($keywords)));
        $params = [':user_id' => $userId]; // Start parameters array with user ID
        $sql = "SELECT `usage_date`, `usage_type`, `usage_amount`, `notes`
                FROM `usage_records`
                WHERE `user_id` = :user_id AND `notes` IS NOT NULL AND `notes` != ''"; // Base query

        // Build keyword search conditions (match if ANY keyword is present)
        if (!empty($keywordList)) {
            $sql .= " AND (";
            $keywordConditions = [];
            foreach ($keywordList as $index => $keyword) {
                $paramName = ":keyword" . $index; // Create unique parameter name
                $keywordConditions[] = "`notes` LIKE " . $paramName; // Use LIKE for partial matching
                $params[$paramName] = '%' . trim($keyword) . '%'; // Add wildcards and bind value
            }
            $sql .= implode(' OR ', $keywordConditions) . ")"; // Combine conditions with OR
        } else {
            // If no valid keywords are provided after trimming, return empty result
            return [];
        }

        // Add optional filters
        if ($usageType !== null && ($usageType === 'electricity' || $usageType === 'water')) {
            $sql .= " AND `usage_type` = :usage_type";
            $params[':usage_type'] = $usageType;
        }
        // Add date filters safely
        try {
            if ($startDate !== null) {
                $startDateTime = (new DateTime($startDate))->format('Y-m-d 00:00:00');
                $sql .= " AND `usage_date` >= :start_date";
                $params[':start_date'] = $startDateTime;
            }
            if ($endDate !== null) {
                $endDateTime = (new DateTime($endDate))->format('Y-m-d 23:59:59');
                $sql .= " AND `usage_date` <= :end_date";
                $params[':end_date'] = $endDateTime;
            }
        } catch (Exception $e) {
             error_log("Invalid date format in searchByNotes: Start=$startDate, End=$endDate - " . $e->getMessage());
             throw new Exception("Invalid date format provided for note search.");
        }

        $sql .= " ORDER BY `usage_date` DESC"; // Show most recent matching notes first

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params); // Execute with all collected parameters
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Format numbers for consistency
            foreach ($results as &$row) {
                $row['usage_amount'] = floatval($row['usage_amount']);
            }
            return $results; // Return array of matching records (can be empty)

        } catch (PDOException $e) {
            error_log("Database Error in searchByNotes: " . $e->getMessage());
            throw new Exception("Failed to search notes due to a database error.");
        }
    }

    /**
     * Calculates the total usage for a given period and optional type.
     * @param int $userId User ID.
     * @param string|null $usageType Optional filter by 'electricity' or 'water'.
     * @param string $startDate Start date ('Y-m-d').
     * @param string $endDate End date ('Y-m-d').
     * @return array Associative array with total usage details.
     */
     public function getTotalUsageForPeriod(int $userId, ?string $usageType, string $startDate, string $endDate): array {
          if ($this->conn === null) {
              error_log("getTotalUsageForPeriod: Database connection is not available.");
              throw new Exception("Database connection is not available.");
          }

          // SQL to sum usage amount within filters
          $sql = "SELECT SUM(`usage_amount`) as total_usage FROM `usage_records`
                  WHERE `user_id` = :user_id AND `usage_date` >= :start_datetime AND `usage_date` <= :end_datetime";
          $params = [':user_id' => $userId];

          // Validate and format dates
          try {
               $startDateTime = (new DateTime($startDate))->format('Y-m-d 00:00:00');
               $endDateTime = (new DateTime($endDate))->format('Y-m-d 23:59:59');
               $params[':start_datetime'] = $startDateTime;
               $params[':end_datetime'] = $endDateTime;
          } catch (Exception $e) {
               error_log("Invalid date format in getTotalUsageForPeriod: Start=$startDate, End=$endDate - " . $e->getMessage());
               throw new Exception("Invalid date format provided for total usage calculation.");
          }

          // Add optional usage type filter
          if ($usageType !== null && ($usageType === 'electricity' || $usageType === 'water')) {
              $sql .= " AND `usage_type` = :usage_type";
              $params[':usage_type'] = $usageType;
          }

          try {
              $stmt = $this->conn->prepare($sql);
              $stmt->execute($params); // Execute with parameters
              $result = $stmt->fetch(PDO::FETCH_ASSOC);
              // Calculate total, defaulting to 0.0 if null
              $total = ($result && $result['total_usage'] !== null) ? floatval($result['total_usage']) : 0.0;

              // Return structured data including the query parameters for context
              return [
                  'total_usage' => $total,
                  'usage_type' => $usageType ?? 'combined', // Report the type queried
                  'period_start' => $startDate,
                  'period_end' => $endDate
              ];

          } catch (PDOException $e) {
              error_log("Database Error in getTotalUsageForPeriod: " . $e->getMessage());
              throw new Exception("Failed to calculate total usage due to a database error.");
          }
     }

    // --- Destructor (Optional) ---
    public function __destruct() {
        // Set connection to null if you want to explicitly close it when the object is destroyed
        // $this->conn = null;
    }
} // End class UsageController
?>
