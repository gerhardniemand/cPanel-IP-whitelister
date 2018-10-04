<?php
/**
* Written by Dowayne Breedt
*/
include("/usr/local/cpanel/php/cpanel.php");
$cpanel = new CPANEL();
print $cpanel->header('IP Whitelist');

#if disabled file is present, display disabled on cPanel
if(file_exists("/usr/local/cpanel/base/frontend/paper_lantern/ip_whitelist/disabled")){
   echo "<h3>The IP Whitelist plugin is currently disabled, please try again later.</h3>";
   exit;
}

//Get locations of config files
$whitelist_file = $_ENV['HOME'].'/.ipwhitelist';
$removed_ips_file = $_ENV['HOME'].'/.removedips';
function countips($file){
  $dots = substr_count($file,'.');
  $noips = $dots/3;
  return $noips;
}
if(isset($_POST['ip_add'])){
 //add the ip to the .ipwhitelist file
 $ip_addr = trim($_POST['ip_add']);
 $valid = filter_var($ip_addr, FILTER_VALIDATE_IP);
 if($valid){
  //get what is already in the file
  $file_ips = file_get_contents($whitelist_file);
  //add ip to list
  $file_ips = $file_ips.$ip_addr.",";
  $noips = countips($file_ips);
  //push ip list to file
  file_put_contents($whitelist_file,$file_ips);
  //check if this IP is in the removed ip's list, and take it out.
  $removed_ips = file_get_contents($removed_ips_file);
  $removed_ips = str_replace($ip_addr.",","",$removed_ips);
  file_put_contents($removed_ips_file,$removed_ips);
  echo "<div class='alert alert-dismissible alert-info info'>".$ip_addr." has been added to whitelist<br><br></div>";
 }else{
  echo "<div class='alert alert-dismissible alert-info info'>".$ip_addr." is not a valid IP</div>".PHP_EOL;
 }
}
if(isset($_POST['ip_remove'])){
 //remove the ip from ipwhitelist file
 //first validate ip, in case someone tries to inject
 $valid = filter_var(trim($_POST['ip_remove']), FILTER_VALIDATE_IP); 
 $ip_addr = trim($_POST['ip_remove'].",");
 if($valid){
   $whitelisted_ips = file_get_contents($whitelist_file);
   $whitelisted_ips = str_replace($ip_addr,"",$whitelisted_ips);
   file_put_contents($whitelist_file,$whitelisted_ips);
   //add IP to removed IP's
   $removed_ips = file_get_contents($removed_ips_file);
   $removed_ips = $removed_ips.$ip_addr;
   file_put_contents($removed_ips_file,$removed_ips);
   $noips = countips($removed_ips);
   echo "<div class='alert alert-dismissible alert-info info'>".$_POST['ip_remove']." has been removed from whitelist<br><br></div>";
 }else{
   //log injection attempt if user tries to manipulate IP address to remove
   echo "<div class='alert alert-dismissible alert-danger'>Injection attept has been logged with Server Administrator.</div>";
   mail("you@yourmail.com","Injection attempt on ".$_ENV['HOST'],"The user tried to inject the following string into the remove function: ".$ip_addr);
 }
}
$ips = split(',',file_get_contents($whitelist_file));
?>
<div id="wrapper">
 <form action="?" method="POST">
  <div class="form-group" style="width:45%;float:left">
   <p>Enter the IP address you wish to whitelist, this will whitelist the IP address from all forms of blocking on our server.</p>
   <p>Please note that, if your IP address is dynamically assigned, it will still be whitelisted, but the moment your IP changes, your new IP can still be blocked</p>
   <p>You can only add one IP at a time, and each IP, will take around a minute to get whitelisted, or removed from the whitelist.</p>
   <input name="ip_add" class="form-control" style="float:left;width:50%;margin:5px" id="exampleInputEmail1" placeholder="Enter IP Address">
   <button type="submit" class="btn btn-primary" style="float:left;margin:5px">Add IP</button>
  </div>
 </form>
 <div class="form-group" style="width:45%;float:right;margin-right:30px;">
   <table class="table table-hover">
  <thead>
    <tr>
      <th scope="col">Whitelisted IP's</th>
      <th scope="col"></th>
    </tr>
  </thead>
  <tbody id="tbody">
   <? foreach ($ips as $ip){?>
    <?if(strlen($ip) < 7){
     //do nothing
    }else{
     $ip = trim($ip);
     ?>
     <tr class="tableactive">
     <input name="ip_remove" value=<? echo "\"".$ip."\";" ?> hidden></input>
     <th scope="row"><? echo $ip; ?></th>
      <td>
       <form action="?" method="post">
         <button value=<? echo "\"".$ip."\";" ?> name="ip_remove" type="submit" class="btn btn-primary">Remove</buttom>
       </form>
      </td>
    </tr>
    <?}}?>
   </tbody> 
   </table>
 </div>
</div>
