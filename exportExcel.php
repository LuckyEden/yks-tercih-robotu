<?php
define ('SITE_ROOT', realpath(dirname(__FILE__)));

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;

use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';



try {
    //Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(false);

    $temp = tmpfile();
    $properName = $_FILES['file']["name"];
    $properPath = SITE_ROOT. '/tmp/uploads/'.$properName;

    move_uploaded_file($_FILES['file']["tmp_name"],  $properPath);
    $targetMail = str_replace("mail=", "", $_SERVER['QUERY_STRING']);
    //Server settings                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'yksakademi.net';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = 'tercih@yksakademi.net';                     //SMTP username
    $mail->Password   = 'Arif1004.';                               //SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
    $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    $mail->CharSet = "UTF-8";
    //Recipients
    $mail->setFrom('tercih@yksakademi.net', 'tercihrobotu.net');
    $mail->addAddress($targetMail);     
    #$mail->addReplyTo('info@example.com', 'Information');
    #$mail->addCC('cc@example.com');
    #$mail->addBCC('bcc@example.com');

    //Attachments
    $mail->addAttachment($properPath, 'tercihlistesi.xlsx');    //Optional name

    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = 'İşte tercih çıktıların! | tercihrobotu.net';
    $mail->Body    = '
    
    <h4>
    Selam dostum,
    </h4>
    <p>
    Bir kaç tercih yapmışsın, işte yaptığın tercihlerin çıktısı!
    </p>
    <p>
    Kendine iyi bak.
    </p>
    <hr>
    <p>
    <a href="https://tercihrobotu.net" target="_blank">
    tercihrobotu.net
    </a>
    </p>
    
    ';
    $mail->AltBody = 'Bir kaç tercih yapmışsın, işte yaptığın tercihlerin çıktısı!';

    $mail->send();
    unlink($properPath);
    echo json_encode(array(
        "status" => 200,
        "message" => 'Mail gönderildi!'
    ));
} catch (Exception $e) {
    #unlink($properPath);
    echo json_encode(array(
        "status" => 500,
        "message" => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"
    ));
}

?>