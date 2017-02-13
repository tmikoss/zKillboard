<?php

global $mdb;

$killID = (int) $killID;
$value = (int) $value;

$userID = User::getUserID();
$charName = Info::getInfoField('characterID', $userID, 'name');
$user = $mdb->findDoc("users", ['userID' => "user:$userID"]);
$adFreeUntil = (int) @$user['adFreeUntil'];
$iskAvailable = floor(max(0, ($adFreeUntil - time()) / (86400 * 30)) * 5000000 * .95);
if ($userID == 1633218082) {
    $iskAvailable = 100000000;
}

$response = "";
$valueF = "";

$referrer = @$_SERVER['HTTP_REFERER'];
if ($referrer != "https://zkillboard.com/kill/$killID/") {
    $app->redirect("/kill/$killID/");
    return;
}

switch ($type) {
    case "query":
        $value = Mdb::group("sponsored", ['killID'], ['killID' => $killID], [], 'isk', ['iskSum' => -1], 5);
        $value = array_shift($value);
        $value = @$value['iskSum'];
        $valueF = number_format($value, 0);
        break;
    case "sponsor":
        $formatted = number_format($value, 0);
        $timeValue = floor(($value / 5000000) * (86400 * 30));

        if ($userID == 0) {
            $response = "You aren't even logged in!";
        } else if ($value == 0) {
            $response = "0 ISK? Come on...";
        } else if (($iskAvailable - $value) < -50000) {
            $response = "Not enough ISK, you requested $value but only have $iskAvailable available.";
        } else {
            $mdb->insert("sponsored", ['characterID' => User::getUserID(), 'isk' => $value, 'killID' => $killID, 'entryTime' => $mdb->now()]);
            $mdb->set("users", ['userID' => "user:$userID"], ['adFreeUntil' => ($adFreeUntil - $timeValue)]);
            ZLog::add("$charName sponsored $formatted ISK for kill $killID", $userID, true);
            $response = "Thank you! You have sponsored $formatted ISK for this kill! Please give the front page up to 2 minutes to reflect your kill sponsorship. Please remember sponsorships will expire after 7 days.";
        }      
        break;
    default:
}

$app->render("sponsor.html", ['killID' => $killID, 'iskA' => $iskAvailable, 'response' => $response, 'valueF' => $valueF, 'type' => $type]);
