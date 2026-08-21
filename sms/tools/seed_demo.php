<?php
// Demo data seeder — uses the app's own bootstrap so data matches the schema.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/MysqliDb.php';

$db = MysqliDb::getInstance();
// Open a direct mysqli connection using the same constants config.php loaded from .env.
$port = defined('DB_PORT') ? (int) DB_PORT : 3306;
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME, $port);
if ($conn->connect_error) { die("DB connect failed: " . $conn->connect_error); }
$conn->set_charset('utf8mb4');

function ins($conn, $table, $data) {
    $cols = '`' . implode('`,`', array_keys($data)) . '`';
    $vals = array_values($data);
    $ph = rtrim(str_repeat('?,', count($vals)), ',');
    $types = str_repeat('s', count($vals));
    $stmt = $conn->prepare("INSERT INTO `$table` ($cols) VALUES ($ph)");
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();
    return $conn->insert_id;
}
echo "Seeder start\n";

// 1) Demo users (manager + staff)
$adminId = $conn->query("SELECT ID FROM `User` LIMIT 1")->fetch_row()[0];
$users = [
  ['name'=>'Marketing Team','email'=>'marketing@grandsms.local','password'=>password_hash('Demo1234',PASSWORD_BCRYPT,['cost'=>12]),'devicesLimit'=>3,'contactsLimit'=>500,'credits'=>1000,'timeZone'=>'UTC','apiKey'=>bin2hex(random_bytes(16))],
  ['name'=>'Support Desk','email'=>'support@grandsms.local','password'=>password_hash('Demo1234',PASSWORD_BCRYPT,['cost'=>12]),'devicesLimit'=>2,'contactsLimit'=>300,'credits'=>500,'timeZone'=>'America/New_York','apiKey'=>bin2hex(random_bytes(16))],
];
foreach ($users as $u) { ins($conn,'User',$u); }
echo "users seeded\n";

// 2) Plans
$plans = [
  ['name'=>'Starter','devices'=>1,'contacts'=>100,'credits'=>200,'price'=>9.99,'currency'=>'USD','frequency'=>1,'frequencyUnit'=>'Month','totalCycles'=>0,'enabled'=>1],
  ['name'=>'Business','devices'=>5,'contacts'=>2000,'credits'=>2000,'price'=>29.99,'currency'=>'USD','frequency'=>1,'frequencyUnit'=>'Month','totalCycles'=>0,'enabled'=>1],
  ['name'=>'Enterprise','devices'=>20,'contacts'=>10000,'credits'=>10000,'price'=>99.99,'currency'=>'USD','frequency'=>1,'frequencyUnit'=>'Year','totalCycles'=>0,'enabled'=>1],
];
foreach ($plans as $p) { ins($conn,'Plan',$p); }
echo "plans seeded\n";

// 3) Devices (multi-user, multi-device)
$devices = [
  ['userID'=>$adminId,'model'=>'Pixel 8','androidVersion'=>'14','appVersion'=>'2.0.1','token'=>bin2hex(random_bytes(20)),'androidID'=>bin2hex(random_bytes(10)),'lastSeenAt'=>date('Y-m-d H:i:s'),'enabled'=>1,'sharedToAll'=>0,'useOwnerSettings'=>1],
  ['userID'=>$adminId,'model'=>'SM-S901B','androidVersion'=>'13','appVersion'=>'2.0.1','token'=>bin2hex(random_bytes(20)),'androidID'=>bin2hex(random_bytes(10)),'lastSeenAt'=>date('Y-m-d H:i:s',strtotime('-2 hours')),'enabled'=>1,'sharedToAll'=>0,'useOwnerSettings'=>1],
  ['userID'=>$adminId,'model'=>'IN2025','androidVersion'=>'12','appVersion'=>'2.0.0','token'=>bin2hex(random_bytes(20)),'androidID'=>bin2hex(random_bytes(10)),'lastSeenAt'=>date('Y-m-d H:i:s',strtotime('-10 min')),'enabled'=>1,'sharedToAll'=>0,'useOwnerSettings'=>1],
];
foreach ($devices as $d) { ins($conn,'Device',$d); }
echo "devices seeded\n";

// 4) SIMs
$sims = [
  ['name'=>'Primary SIM','carrier'=>'Vodafone','country'=>'US','number'=>'+15551234567','slot'=>1,'deviceID'=>1,'enabled'=>1],
  ['name'=>'Secondary SIM','carrier'=>'AT&T','country'=>'US','number'=>'+15557654321','slot'=>2,'deviceID'=>1,'enabled'=>1],
  ['name'=>'Default','carrier'=>'Jio','country'=>'IN','number'=>'+919812345678','slot'=>1,'deviceID'=>3,'enabled'=>1],
];
foreach ($sims as $s) { ins($conn,'Sim',$s); }
echo "sims seeded\n";

// 5) Contacts + lists
$lid = ins($conn,'ContactsList',['name'=>'VIP Customers','userID'=>$adminId]);
$contacts = [
  ['name'=>'Alice Johnson','number'=>'+15551112222','subscribed'=>1,'contactsListID'=>$lid],
  ['name'=>'Bob Smith','number'=>'+15553334444','subscribed'=>1,'contactsListID'=>$lid],
  ['name'=>'Carol Lee','number'=>'+15555556666','subscribed'=>1,'contactsListID'=>$lid],
];
foreach ($contacts as $c) { ins($conn,'Contact',$c); }
echo "contacts seeded\n";

// 6) Templates
$templates = [
  ['userID'=>$adminId,'name'=>'Welcome','message'=>'Welcome to SMS Switch! Your order is confirmed.'],
  ['userID'=>$adminId,'name'=>'OTP','message'=>'Your verification code is {{code}}. Do not share it.'],
  ['userID'=>$adminId,'name'=>'Promo','message'=>'Flash sale! 20% off this weekend. Use code GRAND20.'],
];
foreach ($templates as $t) { ins($conn,'Template',$t); }
echo "templates seeded\n";

// 7) Messages (varied statuses)
$msgs = [
  ['userID'=>$adminId,'deviceID'=>1,'simSlot'=>1,'number'=>'+15551112222','message'=>'Welcome to SMS Switch!','status'=>'Sent','schedule'=>date('Y-m-d H:i:s')],
  ['userID'=>$adminId,'deviceID'=>1,'simSlot'=>2,'number'=>'+15553334444','message'=>'Your OTP is 482913','status'=>'Queued','schedule'=>date('Y-m-d H:i:s',strtotime('+5 min'))],
  ['userID'=>$adminId,'deviceID'=>3,'simSlot'=>1,'number'=>'+919812345678','message'=>'Flash sale 20% off','status'=>'Failed','schedule'=>date('Y-m-d H:i:s',strtotime('-1 hour'))],
  ['userID'=>$adminId,'deviceID'=>1,'simSlot'=>1,'number'=>'+15555556666','message'=>'Appointment reminder','status'=>'Pending','schedule'=>date('Y-m-d H:i:s',strtotime('+1 day'))],
];
foreach ($msgs as $m) { ins($conn,'Message',$m); }
echo "messages seeded\n";

// 8) USSD requests
ins($conn,'Ussd',['userID'=>$adminId,'deviceID'=>1,'simSlot'=>1,'request'=>'*123#','response'=>'Balance: $4.50','sentDate'=>date('Y-m-d H:i:s'),'responseDate'=>date('Y-m-d H:i:s')]);
echo "ussd seeded\n";

// 9) Auto responders
$resp = [
  ['userID'=>$adminId,'message'=>'STOP','response'=>'You have been unsubscribed.','matchType'=>'exact','enabled'=>1],
  ['userID'=>$adminId,'message'=>'HELP','response'=>'Reply HELP for support or call 1-800-GRAND.','matchType'=>'exact','enabled'=>1],
  ['userID'=>$adminId,'message'=>'INFO','response'=>'Visit grand-sms.example for plans.','matchType'=>'contains','enabled'=>1],
];
foreach ($resp as $r) { ins($conn,'Response',$r); }
echo "autoresponders seeded\n";

// 10) Blacklist
ins($conn,'Blacklist',['userID'=>$adminId,'number'=>'+15559998888']);
echo "blacklist seeded\n";

// 11) Subscriptions + payments
$subId = ins($conn,'Subscription',['userID'=>$adminId,'planID'=>2,'subscribedDate'=>date('Y-m-d H:i:s'),'cyclesCompleted'=>0,'status'=>'active','paymentMethod'=>'manual']);
ins($conn,'Payment',['subscriptionID'=>$subId,'amount'=>29.99,'transactionFee'=>0.89,'currency'=>'USD','dateAdded'=>date('Y-m-d H:i:s'),'userID'=>$adminId,'status'=>'completed']);
echo "subscriptions seeded\n";

echo "ALL DEMO DATA SEEDED OK\n";
