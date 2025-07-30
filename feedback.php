<?php
if(isset($_POST['message'])) {
     
    // EDIT THE 2 LINES BELOW AS REQUIRED
    
    $email_to = "sales@triptigases.com,venugopal_d@triptigases.com,venu1681@gmail.com";
    $bcc = "priya@ttechnologies.in";
    $email_subject = "Feedback Form Submission on Tripti Dry Ice Website";
    $email_from = "support@ttechnologies.in";
    //$res = "";
     //echo $email_to. '<br />';
     //echo $email_subject. '<br />';
     //echo $email_from. '<br />';
     
    function died($error) {
        // your error code can go here
	$msg = 'We are very sorry, but there were error(s) found with the form you submitted. \n \n These errors appear below: \n ' .$error .'\n Please go back and fix these errors.';
	echo $msg;
	//echo "<script type='text/javascript'>alert('{$msg}');</script>";
	        //echo "We are very sorry, but there were error(s) found with the form you submitted. <br />";
        //echo "These errors appear below.<br /><br />";
        //echo $error."<br /><br />";
        //echo "Please go back and fix these errors.<br /><br />";
        die();
    }
     
     $error_message = "";
    if(isset($_POST['name']))
    {	
    	$name = $_POST['name']; // required
    	
    	$string_exp = "/^[A-Za-z .'-]+$/";
    	if(!preg_match($string_exp,$name)) {
    		$error_message .= 'The Name you entered does not appear to be valid.\n';
    	}
    }else $name = 'Name not mentioned';
    if(isset($_POST['email']))
    {
    	$email = $_POST['email']; // required
    	$email_exp = '/^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/';
    	
    	if(!preg_match($email_exp,$email)) {
    		$error_message .= 'The Email Address you entered does not appear to be valid.\n';
    	}
    }else $email = 'Email id not mentioned';
    if(isset($_POST['number']))
    {
    	$number = $_POST['number']; // required
    	
    	if(!preg_match('/^(NA|[0-9+-]+)$/',$number))  {
    		$error_message .= 'The Contact Number you entered does not appear to be valid.\n';
    	}
    }else $number = 'Number not mentioned';
    
    $message = $_POST['message']; // required
     
  
  if(strlen($message) < 2) {
    $error_message .= 'The Message you entered does not appear to be valid.\n';
  }
  if(strlen($error_message) > 0) {
    died($error_message);
  }
    $email_message = "Please find Your Feedback Form details below.<br />";
     
    function clean_string($string) {
      $bad = array("content-type","bcc:","to:","cc:","href");
      return str_replace($bad,"",$string);
    }
    
    $email_message .= "Name: ".clean_string($name)."<br />";
    $email_message .= "Email: ".clean_string($email)."<br />";
    $email_message .= "Contact Number: ".clean_string($number)."<br />";
    $email_message .= "Message: ".clean_string($message)."<br />";
    
     
// create email headers
$headers = 'From: '.$email_from."\r\n";
$headers .= 'Reply-To: '.$email_from."\r\n" ;
$headers .= "Content-Type:text/html; charset=\"iso-8859-1\"\n" ;
$headers .= 'BCC: '.$bcc."\r\n";
//$headers .= 'X-Mailer: PHP/' . phpversion()"\r\n" ;


if (mail($email_to, $email_subject, $email_message, $headers)) 
	{
	//echo 'Mail sent<br />'; 
	echo 'Thank you for your Feedback.';
	
	}
	else
	{
	  echo 'There was some problem in sending the contact form. Please try again later.';
	 
	  
	}

 
}
?>