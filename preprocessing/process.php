<?php
	/**
	 * NYC Restaurant
	 * Collects data from nycopendata and uses it to find the top 10 restaurants filtered by food type
	 *
	 * Logic: this script would act as a cronjob (per day) to download the csv file and process its data
	 *	1. Download the csv
	 *	2. Perform prerequisite checks on the file downloaded to make sure its safe to process
	 *	3. Open file and process line by line, delimitting line by comma
	 *	4. Make sure that the data we're inserting is not duplicate. This ensures that the script will run faster each time its run
	 *	5. Compute the top 10 food variance based on foodtype
	 *
	 *	Structure wise, I decided to create a staging layer that would contain all the noisy data waiting to be processed
	 *	All the transforming would be processed inside staging, and then moved to prod for usage. This would provide faster access.
	 *	Both databases would implement indexes for all important keys such as camis
	 *
	 *	I also made it a choice to pull the data for all food types - this way, if my friend's taste changes from Thai food to Hamburgers, the application would be able to support his lifestyle
	 *
	 *	Finally, I thought it might be important to store the historical inspection data - with it, I can provide statistics on how the restaurant performed over time.
	 *
	 *	First Run: 6.8hrs to finish loading all 500,000 into the database (keep in mind, this aggregated historical, non duplicate data)
	 *	Second Run: 1.2hrs to run through all data, validating existing
	 *	Third Run (after indexing was done): 0.4hrs
	 *
	 *	Author: Zill Christian
	 *	Credit: socrata nycopendata
	 */

	// Setting default timezone
	date_default_timezone_set('America/New_York');

	// CSV file url
	$csv_file_url = "https://nycopendata.socrata.com/api/views/xx67-kt59/rows.csv?accessType=DOWNLOAD";

	// csv file header indexes
	$headers = array(
		'id'		=> 0,	// camis
		'name'		=> 1,	// dba
		'boro'		=> 2,	// boro
		'building'	=> 3,	// building
		'street'	=> 4,	// street
		'zipcode'	=> 5,	// zipcode
		'phone'		=> 6,	// phone
		'type'		=> 7,	// cuisine description
		'i_date'	=> 8,	// inspection date
		'action'	=> 9,	// action
		'code'		=> 10,	// violation code
		'desc'		=> 11,	// violation description
		'flag'		=> 12,	// critical flag
		'score'		=> 13,	// score
		'grade'		=> 14,	// grade
		'g_date'	=> 15,	// grade date
		'r_date'	=> 16,	// record date
		'i_type'	=> 17	// inspection type
	);

	// Cols required to process data line
	$requiredCols = ['id', 'name', 'type', 'i_date', 'score'];

	// Download file
	$filename = downloadCSVFile($csv_file_url);

	// Collect the env from arguments if it's set, otherwise default to local
	$env = !empty($argv[1]) && in_array(strtolower($argv[1]), ['prod', 'stg', 'local']) ? $argv[1] : 'local';

	// Collect a database handle
	$dbh = getDBHandle($env);
	
	// Initiate the process
	processCSV($filename, $env, $headers, $requiredCols, $dbh);

	// Let's finally get to that thai food
	computeFoodVariance('thai', $dbh);


	/**
	 * Function initiates processing of the csv file
	 * @param $file [String] name of the local file
	 * @param $env [String] environment to apply data to
	 * @param $headers [Object] Dictionary containing the row headers/location
	 * @param $requiredCols [Array] Contains the list of rows that are needed for processing
	 * @param $dbh [Object] mysqli database handler
	 */
	function processCSV($file, $env, $headers, $requiredCols, $dbh) {
		if (!preReqChecks($file))
			exit();

		$index = 0;

		if ($fh = fopen($file, "r")) {
			while ($data = fgetcsv($fh, 0, ",")) {
				
				// Skipping header
				if ($index == 0) {
					$index += 1;
					continue;
				}

		        // Check if this row needs to be skipped
				if (skipRow($data, $headers, $requiredCols))
					continue;

				// Initiate database insertion
				insertIntoDatabase($data, $headers, $dbh);

				// Increment counter
				$index += 1;
		    }
		    fclose($fh);
		}
	}


	/**
	 * Function inserts data into the database
	 * @param $data [array] array containing data from one row from csv
	 * @param $headers [array] dictionary translating the headers to row indexes
	 * @param $dbh [Object] mysqli database handler
	 */
	function insertIntoDatabase($data, $headers, $dbh) {
		$camis		= $data[$headers['id']];
		$name		= $data[$headers['name']];
		$boro		= $data[$headers['boro']];
		$building	= $data[$headers['building']];
		$street		= $data[$headers['street']];
		$zipcode	= $data[$headers['zipcode']];
		$phone		= $data[$headers['phone']];
		$type		= $data[$headers['type']];
		$i_date		= $data[$headers['i_date']];
		$action		= $data[$headers['action']];
		$code		= $data[$headers['code']];
		$desc		= $data[$headers['desc']];
		$flag		= $data[$headers['flag']];
		$score		= $data[$headers['score']];
		$grade		= $data[$headers['grade']];
		$g_date		= $data[$headers['g_date']];
		$r_date		= $data[$headers['r_date']];
		$i_type		= $data[$headers['i_type']];

		// Collect restaurant id
		$rest_id = getRestaurantID($camis, $dbh);

		// Checking if camis already exists
		if ($rest_id==0) {
			$address_id = createAddress($boro, $building, $street, $zipcode, $phone, $dbh);
			$cuisine_type_id = createCuisineType($type, $dbh);
			$rest_id = createRestaurant($camis, $name, $address_id, $cuisine_type_id, $dbh);
		}

		// if the inspection doesn't exist create it
		if (!inspectionExists($i_date, $i_type, $score, $grade, $dbh)) {
			$violationID = getViolationID($code, $desc, $flag, $dbh);
			$inspectionID = createInspection($i_date, $violationID, $i_type, $score, $grade, $dbh);

			// Connect both records
			tieRestaurantToInspection($rest_id, $inspectionID, $dbh);
		}
	}

	/**
	 * Connects unique Restaurants to multiple Inspections
	 * @param $rest_id [int] unique restaurant record id
	 * @param $inspectionID [int] primary id of inspection id
	 * @param $dbh [Object] mysqli database handler
	 */
	function tieRestaurantToInspection($rest_id, $inspectionID, $dbh) {
		$stmt = $dbh->prepare("INSERT INTO restaurant_to_inspection(restaurant_id, inspection_id) VALUES(?, ?)");
        $stmt->bind_param('ii', $rest_id, $inspectionID);
        
        if (!$stmt->execute()) {
            echo "Restaurant_to_Inspection insert failed: (" . $stmt->errno . ") " . $stmt->error;
            exit();
        }
	}

	/**
	 * Returns restaurant id
	 * @param $camis [String] unique identifier for restaurant
	 * @param $dbh [Object] mysqli database handler
	 * @return [int] id of the restaurant record
	 */
	function getRestaurantID($camis, $dbh) {
		$rest_id = 0;

		// Checking if it exists already
		if ($result = $dbh->query("SELECT id FROM restaurant WHERE camis = '".$camis."'"))
			while ($row = $result->fetch_assoc())
				$rest_id = $row['id'];

		return $rest_id;
	}

	/**
	 * Returns violation id if it exists, otherwise it creates it and then returns the new id
	 * @param $code [String] violation code
	 * @param $desc [String] violation description
	 * @param $flag [String] a string that gets converted to a boolean
	 * @param $dbh [Object] mysqli database handler
	 * @return [int] returns the violation id
	 */
	function getViolationID($code, $desc, $flag, $dbh) {
		$violationID = null;

		// cast flag to boolean for consistent storage
		$flag = strtolower($flag)=='critical' ? 1 : 0;

		// Checking if it exists already
		if ($result = $dbh->query("SELECT * FROM violation WHERE code = '".$code."' AND description = '".$desc."' AND flag = '".$flag."' "))
			while ($row = $result->fetch_assoc())
				$violationID = $row['id'];

		// return if found already
		if (!empty($violationID))
			return $violationID;

		// Since the id wasn't found, create it and return the new id
		$stmt = $dbh->prepare("INSERT INTO violation(code, description, flag) VALUES(?, ?, ?)");
        $stmt->bind_param('ssi', $code, $desc, $flag);
        
        if (!$stmt->execute()) {
            echo "Violation insert failed: (" . $stmt->errno . ") " . $stmt->error;
            exit();
        }

        return $stmt->insert_id;
	}

	/**
	 * Creates an inspection record if it doesn't exist
	 * @param $i_date [String] inspection date
	 * @param $violationID [int] primary id of the violation record
	 * @param $i_type [String] inspection type
	 * @param $score [int] depicts health score
	 * @param $grade [String] the health grade
	 * @param $dbh [Object] mysqli database handler
	 */
	function createInspection($i_date, $violationID, $i_type, $score, $grade, $dbh) {
		$stmt = $dbh->prepare("INSERT INTO inspection(inspection_date, violation_id, type, score, grade) VALUES(STR_TO_DATE(?, '%m/%d/%Y'), ?, ?, ?, ?)");
        $stmt->bind_param('sisis', $i_date, $violationID, $i_type, $score, $grade);
        
        if (!$stmt->execute()) {
            echo "Inspection row insert failed: (" . $stmt->errno . ") " . $stmt->error;
            exit();
        }

        return $stmt->insert_id;
	}	

	/**
	 * Function returns a boolean depending on whether the inspection exists or not
	 * @param $i_date [String] inspection date
	 * @param $i_type [String] inspection type
	 * @param $score [int] depicts health score
	 * @param $grade [String] the health grade
	 * @param $dbh [Object] mysqli database handler
	 * @return [bool] returns a boolean depending on whether the inspection exists or not
	 */
	function inspectionExists($i_date, $i_type, $score, $grade, $dbh) {
		$inspections = array();

		// Checking if it exists already
		if ($result = $dbh->query("SELECT * FROM inspection WHERE type = '".$i_type."' AND inspection_date = STR_TO_DATE('".$i_date."', '%m/%d/%Y') AND grade = '".$grade."' "))
			while ($row = $result->fetch_assoc())
				$inspections[] = $row;

		return !empty($inspections);
	}

	/**
	 * Creates and returns id of cuisine type if it doesn't alreadt exist
	 * @param $type [String] the type of cuisine the restaurant serves
	 * @return [int] the primary row id of the cuisine_type
	 */
	function createCuisineType($type, $dbh) {
		$cuisine_type_id = null;

		// Checking if it exists already
		if ($result = $dbh->query("SELECT * FROM cuisine_type WHERE type = '".$type."'"))
			while ($row = $result->fetch_assoc())
				if (!empty($row['type']))
					$cuisine_type_id = $row['type'];

		// return if found already
		if (!empty($cuisine_type_id))
			return $cuisine_type_id;

		// Since the id wasn't found, create it and return the new id
		$stmt = $dbh->prepare("INSERT INTO cuisine_type(type) VALUES(?)");
        $stmt->bind_param('s', $type);
        
        if (!$stmt->execute()) {
            echo "Cuisine type insert failed: (" . $stmt->errno . ") " . $stmt->error;
            exit();
        }

        return $stmt->insert_id;
	}

	/**
	 * Creates a new address row and returns its row id
	 * @param $boro [String]
	 * @param $building [String]
	 * @param $street [String]
	 * @param $zipcode [String]
	 * @param $phone [String]
	 * @return [int] primary id of the row in the database
	 */
	function createAddress($boro, $building, $street, $zipcode, $phone, $dbh) {
		$stmt = $dbh->prepare("INSERT INTO address(boro, building, street, zip, phone) VALUES(?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $boro, $building, $street, $zipcode, $phone);
        
        if (!$stmt->execute()) {
            echo "Address row insert failed: (" . $stmt->errno . ") " . $stmt->error;
            exit();
        }

        return $stmt->insert_id;
	}

	/**
	 * Creates a new restaurant and returns its row id
	 * @param $camis [String]
	 * @param $name [String]
	 * @param $address_id [String]
	 * @param $cuisine_type_id [String]
	 * @return [int] primary id of the row in the database
	 */
	function createRestaurant($camis, $name, $address_id, $cuisine_type_id, $dbh) {
		$stmt = $dbh->prepare("INSERT INTO restaurant(camis, name, address_id, cuisine_type_id) VALUES(?, ?, ?, ?)");
        $stmt->bind_param('ssii', $camis, $name, $address_id, $cuisine_type_id);
        
        if (!$stmt->execute()) {
            echo "Restaurant row insert failed: (" . $stmt->errno . ") " . $stmt->error;
            exit();
        }

        return $stmt->insert_id;
	}

	/**
	 * Returns true if a camis already exists in the database
	 * @param $camis [String]
	 * @param $dbh [Object] mysqli database handler
	 */
	function duplicateCamis($camis, $dbh) {
		$camis = array();

		if ($result = $dbh->query("SELECT * FROM restaurant WHERE camis = '".$camis."'"))
			while ($row = $result->fetch_assoc())
				$camis[] = $row['camis'];

		return !empty($camis);
	}

	/**
	 * Returns existing distinct Camis from the database
	 * @param $dbh [Object] mysqli database handler
	 * @return [array] an array (hash) containing all unique camis
	 */
	function collectExistingCamis($dbh) {
		$camisArr = array();
		
		$query = "SELECT r.*, ct.type, a.boro, a.building, a.street, a.zip, a.phone FROM restaurant r LEFT JOIN cuisine_type ct ON r.cuisine_type_id=ct.id LEFT JOIN address a ON r.address_id=a.id";
		
		if ($result = $dbh->query($query))
			while ($row = $result->fetch_assoc())
				$camisArr[$row['camis']] = $row;

		return $camisArr;
	}

	/**
	 * Generates the top 10 records to insert into top_10_restaurants table for faster app access
	 * @param $dbh [Object] mysqli database handler
	 */
	function computeFoodVariance($foodType, $dbh) {
		/* 
			We can allow the top_10_restaurants table to maintain performance by trimming excess rows
			The cost of this is historical data. If you want to preserve historical data, set below to false
		*/
		$maintainPerformance = true;
		$limit = 10;
        $restaurants = array();

		$query = "SELECT * FROM restaurant r LEFT JOIN cuisine_type c ON c.id=r.`cuisine_type_id` LEFT JOIN address a ON a.id=r.`address_id` LEFT JOIN restaurant_to_inspection ri ON ri.`restaurant_id`=r.`id` LEFT JOIN inspection i ON i.`id`=ri.`inspection_id` WHERE c.`type`='thai' AND i.inspection_date > DATE_SUB(CURDATE(), INTERVAL 1 YEAR) AND grade IN ('A', 'B') ORDER BY score DESC";

		if ($result = $dbh->query($query)) {
			while ($row = $result->fetch_assoc()) {
				$camis = $row['camis'];
				// First one should already be sorted and highest score within last year so we choose this
				if (empty($restaurants[$camis]))
					$restaurants[$camis] = $row;
			}
		}

		$index = 0;
		// For each restaurant collected, insert into the top_10_restaurants table for faster access
		foreach ($restaurants as $camis => $row) {
			// only insert as much as we need
			if ($index == $limit)
				break;

			$name = trim($row['name']);
			$address = trim($row['building']) . ' ' . trim($row['street']) . ', ' . trim($row['boro']) . ' ' . trim($row['zip']);
			$phone = trim($row['phone']);
			$score = $row['score'];
			$grade = trim($row['grade']);

			$stmt = $dbh->prepare("INSERT INTO top_10_restaurants (restaurant, address, phone, score, grade) VALUES(?, ?, ?, ?, ?)");
	        $stmt->bind_param('sssis', $name, $address, $phone, $score, $grade);
	        
	        if (!$stmt->execute()) {
	            echo "top_10_restaurants row insert failed: (" . $stmt->errno . ") " . $stmt->error;
	            exit();
	        }

	        $index++;
		}

        if ($maintainPerformance) {
	        // Lets set a nice limit to this table so it can maintain its performance
	        $currCount = 0;
	        $query = "SELECT COUNT(*) AS CurrCount FROM top_10_restaurants";
			if ($result = $dbh->query($query))
				while ($row = $result->fetch_assoc())
					$currCount = $row['CurrCount'];

			// If table if 5x it's limit size, we trim rows by half (performance reason)
			if ($currCount > ($limit*5)) {
				$numToDelete = $currCount / 2;
				$stmt = $dbh->prepare("DELETE FROM top_10_restaurants ORDER BY insert_timestamp LIMIT ?");
				$stmt->bind_param('i', $numToDelete);

				if (!$stmt->execute())
					echo "Could not delete from top_10_restaurants: (" . $stmt->errno . ") " . $stmt->error;
			}
		}
	}

	/**
	 * Returns true if the row needs to be skipped
	 * @param $data [array] contains information of the row
	 * @param $headers [array] contains the dictionary from col to index
	 * @param $requiredCols [array] contains the cols that are required to process
	 * @return [bool] true if the row needs to be skipped, false otherwise
	 */
	function skipRow($data, $headers, $requiredCols) {
		// Making sure required columns are not empty
		foreach ($requiredCols as $col)
			if (empty($data[$headers[$col]]))
				return true;

		// Making sure score is numeric
		if (!is_numeric($data[$headers['score']]))
			return true;

		return false;
	}

	/**
	 * Runs through some checks before starting process
	 * @param $file [String] name of the csv file to process
	 * @return [bool] True if all is good, False otherwise
	 */
	function preReqChecks($file) {
		$isSuccess = true;

		if (!file_exists($file)) {
			$isSuccess = false;
			echo "Error: File is not readable";
		}

		if (!is_readable($file)) {
			$isSuccess = false;
			echo "Error: File is not readable";
		}

		return $isSuccess;
	}

	/**
	 * Downloads a csv file into the local csvfiles directory
	 * @param $url [String] direct url to csv file
	 * @return [String] name of the file the csv was saved to
	 */
	function downloadCSVFile ($url) {
		// generate filename
		$file_path = getcwd() . "/csvfiles/" . uniqid() . ".csv";

		// download the file in a stream
		file_put_contents($file_path, fopen($url, 'r'));

		// ensure the file actually got downloaded
		if (!file_exists($file_path))
			throw new Exception("CSV file could not be downloaded to " . $file_path);

		// return file path
		return $file_path;
    }

    /**
     * Returns database handle
     * @param $env [String] environment to collect the handle of
     * @return [Object] mysqli database handler
     */
    function getDBHandle($env) {
        $dbenv = array(
            "prod" => array(
                "host"      => "localhost",
                "user"      => "root",
                "pass"      => "root",
                "database"  => "nyc_restaurants_prod"
            ),
            "stg" => array(
                "host"      => "localhost",
                "user"      => "root",
                "pass"      => "root",
                "database"  => "nyc_restaurants_stg"
            ),
            "local" => array(
                "host"      => "localhost",
                "user"      => "root",
                "pass"      => "root",
                "database"  => "nyc_restaurants"
            )
        );

        // Setting default env to staging
        $defaultEnv = "local";
        if (!in_array(strtolower($env), array_keys($dbenv)))
            $env = $defaultEnv;

        $dbh = new mysqli($dbenv[$env]['host'], $dbenv[$env]['user'], $dbenv[$env]['pass'], $dbenv[$env]['database']);
        if ($dbh->connect_errno > 0) {
            echo('\nUnable to connect to database [' . $dbh->connect_error . ']\n');
            exit();
        }

        return $dbh;
    }	
?>