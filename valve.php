<?php

class valve {

	var $apiKey;
	var $dsn = 'mysql:host=localhost;dbname=valve';
	var $dbuser = 'USER';
	var $dbpwd = 'PASSWORD';

	public function surslash($string) {
		return '"' . $string . '"';
	}

	public function get_contrib() {

		//GET DATA FROM MANAGED MISSIONS
		$ch = curl_init('https://app.managedmissions.com/API/ContributionAPI/List?apiKey=' . $this->apiKey);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$json = json_decode($result, true);

		//CONNECT TO DATABASE
		try {
			$db = new PDO($this->dsn,$this->dbuser,$this->dbpwd);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$status = "Success";
		} catch(Exceptions $e) {
			echo $e->getMessage();
		}

		//IF CONNECTION TO THE DATABASE IS SUCCESFUL CREATE OUR STAGING TABLE
		if($status == "Success") {
			$newTable = <<<SQL
				CREATE TABLE IF NOT EXISTS valve_test_db (
					id INT NOT NULL PRIMARY KEY,
					contribDay INT NOT NULL,
					trip VARCHAR(55) COLLATE utf8_general_ci,
					refNum VARCHAR(30) COLLATE utf8_general_ci,
					donorName VARCHAR(140) COLLATE utf8_general_ci,
					contribAmount INT NOT NULL,
					phoneNumber VARCHAR(15) COLLATE utf8_general_ci,
					addressOne VARCHAR(140) COLLATE utf8_general_ci,
					addressTwo VARCHAR(140) COLLATE utf8_general_ci,
					city VARCHAR(30) COLLATE utf8_general_ci,
					state VARCHAR(30) COLLATE utf8_general_ci,
					postalCode INT,
					emailAddress VARCHAR(255) COLLATE utf8_general_ci,
					status VARCHAR(13) COLLATE utf8_general_ci DEFAULT 'pending'
					)
SQL;

			$db->exec($newTable);
		}

		foreach( $json['data'] as $contrib ) {

			$id = $contrib['Id'];

			//SET MM RESULTS TO VARIABLES
			$date = str_replace('/Date(', '', $contrib['DepositDate']);
			$date = str_replace(')/', '', $date);
			$date = substr($date, 0, -3);
			$contribDay = $date;

			$trip = $this->surslash(htmlspecialchars($contrib['MissionTripName']));
			$refNum = $this->surslash(htmlspecialchars($contrib['ReferenceNumber']));
			$donorName = $this->surslash(addslashes(htmlspecialchars($contrib['DonorName'])));
			$contribAmount = intval($contrib['ContributionAmount']);

			if ($contrib['PhoneNumber'] != NULL) {
				$phoneNumber = $this->surslash(htmlspecialchars($contrib['PhoneNumber']));

			} else {
				$phoneNumber = 'NULL';
			}

			if ($contrib['Address1'] != NULL) {
				$addressOne = $this->surslash(htmlspecialchars($contrib['Address1']));
			} else {
				$addressOne = 'NULL';
			}

			if ($contrib['Address2'] != NULL) {
				$addressTwo = $this->surslash(htmlspecialchars($contrib['Address2']));
			} else {
				$addressTwo = 'NULL';
			}

			if ($contrib['City'] != NULL) {
				$city = $this->surslash(htmlspecialchars($contrib['City']));
			} else {
				$city = 'NULL';
			}

			if ($contrib['State'] != NULL) {
				$state = $this->surslash(htmlspecialchars($contrib['State']));
			} else {
				$state = 'NULL';
			}

			if ($contrib['PostalCode'] != NULL) {
				$postalCode = intval($contrib['PostalCode']);
			} else {
				$postalCode = 'NULL';
			}

			if ($contrib['EmailAddress'] != NULL) {
				$emailAddress = $this->surslash(htmlspecialchars($contrib['EmailAddress']));
			} else {
				$emailAddress = 'NULL';
			}

			//GRAB ID's FROM DATABASE
			$search = $db->query('SELECT id FROM valve_test_db WHERE id = ' . $id);
			$result = $search->fetch(PDO::FETCH_ASSOC);

			//CHECK IF ENTRY IS ALREADY IN DB, IF IS MOVE ON TO NEXT ENTRY
			if($result['id'] == $id) {
				continue;
			//IF ENTRY DOES NOT EXIST IN DB, SUBMIT ENTRY TO DB
			} else {
				try {
					$newEntry = "INSERT INTO valve_test_db (
						id,
						contribDay,
						trip,
						refNum,
						donorName,
						contribAmount,
						phoneNumber,
						addressOne,
						addressTwo,
						city,
						state,
						postalCode,
						emailAddress
					) VALUES (
						 ".$id.",
						".$contribDay.",
						".$trip.",
						".$refNum.",
						".$donorName.",
						".$contribAmount.",
						".$phoneNumber.",
						".$addressOne.",
						".$addressTwo.",
						".$city.",
						".$state.",
						".$postalCode.",
						".$emailAddress."
						)";
					$db->exec($newEntry);
				} catch(Exception $e) {
					echo $e->getMessage();
				}
			}

		}

	}

	public function submit_to_f1() {
		//CONNECT TO DATABASE
		try {
			$db = new PDO($this->dsn,$this->dbuser,$this->dbpwd);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$status = "Success";
			echo $status;
		} catch (Exception $e) {
			echo $e->getMessage();
		}

		//SEARCH REFERENCE NUMBERS FOR CREDIT CARD PAYMENTS
		$searchRef = $db->query("SELECT * FROM valve_test_db WHERE refNum = 'Credit Card' AND status = 'pending'");
		$resultRef = $searchRef->fetchAll(PDO::FETCH_ASSOC);

		//WE WILL HAVE TO PLUG EACH OF THESE INTO HERE
		foreach($resultRef as $result) {
			echo "<ul>";
			echo "<li>".$result['donorName']."</li>";
			echo "<li>".$result['emailAddress']."</li>";
			echo "<li>".$result['phoneNumber']."</li>";
			echo "<li>".$result['addressOne']."</li>";
			echo "</ul>";
			echo "<br>";
		}

	}

	public function searchF1Households($f1) {

		try {
			$db = new PDO($this->dsn,$this->dbuser,$this->dbpwd);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$status = "Success";
			echo $status;
		} catch (Exception $e) {
			echo $e->getMessage();
		}

		$searchHouse = $db->query("SELECT * FROM valve_test_db WHERE refNum = 'Credit Card' AND status = 'pending'");
		$resultHouse = $searchHouse->fetchAll(PDO::FETCH_ASSOC);

		foreach ($resultHouse as $result) {
			$addressOne = $result['addressOne'];
			$lname = $result['donorName'] ;
			$cid = $result['id'];
			//var_dump($cid);

			$r = $f1->searchPeople(array(//search attributes
					"searchFor"=>$lname,
					"address"=>$addressOne,
				));

				if($r && $r['results']['@count']<=0){
					$model = $f1->householdModel;
					//var_dump($model);//see model structor
					$model['household']['householdName'] = $result['donorName'];
					$r = $f1->createHousehold($model);

					// var_dump($r);

					$personSplody = explode(" ", $result['donorName']);
					$firstName = $personSplody[0];
					$lastName = $personSplody[1];

					$householdRID = $r['household']['@id'];

					$modelP = $f1->personModel;

					$modelP['person']['@householdID'] = $householdRID;
					$modelP['person']['householdMemberType']['@id'] = '1';
					$modelP['person']['status']['@id'] = '7';
					$modelP['person']['firstName'] = $firstName;
					$modelP['person']['lastName'] = $lastName;
					$r = $f1->createPerson($modelP);

					$prid = $r['person']['@id'];

					$modelA = $f1->getAddressModel($prid);

					$modelA['address']['@householdID'] = $householdRID;
					$modelA['address']['address1'] = $result['addressOne'];
					$modelA['address']['address2'] = $result['addressTwo'];
					$modelA['address']['city'] = $result['city'];
					$modelA['address']['stProvince'] = $result['state'];
					$modelA['address']['postalCode'] = $result['postalCode'];
					$r = $f1->createAddress($modelA,$prid);

					// $this->searchF1Households($f1);

				}

				if($r && $r['results']['@count']>0){
					foreach($r['results']['person'] as $person){
						//var_dump($person);
						echo "<br>";
						//setting up parameters for receipt submission
						$householdID = $person['@householdID'];
						$cd = date("Y-m-d\TH:i:sP", $result['contribDay']);
						$receivedDate = new DateTime($cd);
						$missionFundID = 4794;
						$voucherID = 3;
						$subtypeID = 2520;
						$amount = $result['contribAmount'];

						$subs = $f1->getGivingSubFunds(4794);
						foreach($subs['subFunds']['subFund'] as $sub) {
				      if( $sub['name'] == $result['trip']) {
								$subFundID = $sub['@id'];
								break;
							}
				    }

						// echo $householdID . "\n";
						// echo $householdName ."\n";
						// echo $amount . "\n";

						//create a new model for contribution
						$model = $f1->contributionReceiptModel;

						//set attributes
						$model['contributionReceipt']['fund']['@id'] = (int) $missionFundID;
						$model['contributionReceipt']['subFund']['@id'] = (int) $subFundID;
						$model['contributionReceipt']['receivedDate'] = $receivedDate->format(DATE_ATOM);
						$model['contributionReceipt']['contributionType']['@id'] = (int) $voucherID;
						$model['contributionReceipt']['contributionSubType']['@id'] = (int) $subtypeID;
						$model['contributionReceipt']['amount'] = (float) $amount;
						$model['contributionReceipt']['household']['@id'] = (int) $householdID;
						$model['contributionReceipt']['memo'] = 'Managed Missions';

						// var_dump($model);
						//send the data to F1
						echo "<pre>";
						print_r($model);
						$r = $f1->createContributionReceipt($model);
						if($r) {
							$sql = 'UPDATE valve_test_db SET status = "submitted" WHERE id = '. $cid;
							$db->exec($sql);
							echo "Congrats! It worked!";
							echo "<pre>";
							var_dump($r);
						}



					}
				}

			}
	}

	public function getFundies($f1) {
		$subs = $f1->getGivingSubFunds(4794);
		foreach($subs['subFunds']['subFund'] as $sub) {
			if( $sub['name'] == 'DR Worship Team') {
				$subname = $sub['@id'];
				break;
			}
		}
		return $subname;
	}

	public function getModel($f1){
		echo "<pre>";
		var_dump($f1->addressModel);
	}

}
