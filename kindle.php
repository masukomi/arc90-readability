<?php
    set_include_path( get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/lib');
    session_start();

	// Strip any slashes from POSTed params
	foreach( $_POST as $postIndex=>$postVar ) {
		if( get_magic_quotes_gpc() ) {
			$_POST[$postIndex] = stripslashes($postVar);
		}

		$_POST[$postIndex] = utf8_encode($_POST[$postIndex]);
	}

    require_once 'Zend/Filter.php';
    require_once 'Zend/Filter/StripTags.php';
    require_once 'Zend/Filter/StringTrim.php';

    $filters = new Zend_Filter();
    $filters->addFilter(new Zend_Filter_StripTags())
            ->addFilter(new Zend_Filter_StringTrim());

    $pageTitle   = array_key_exists('pageTitle', $_POST) ? $filters->filter($_POST['pageTitle']) : '';
    $pageUrl     = array_key_exists('pageUrl', $_POST) ? $filters->filter($_POST['pageUrl']) : '';
	$bodyContent = array_key_exists('bodyContent', $_POST) ? $_POST['bodyContent'] : '';
    $page        = 'form';
    $errors      = array();

	$bodyContent = '
	<!DOCTYPE html>
	<html>
		<head>
			<style type="text/css">' .
				file_get_contents('css/readability.css') . '
			</style>
			<title>Readability</title>
		</head>
		<body class="style-newspaper">
			<div id="readOverlay" class="style-newspaper">
				<div id="readInner">
					<div id="readability-content">' .
						$bodyContent . '
					</div>
				</div>
			</div>
		</body>
	</html>';
	
    if('post' == strtolower($_SERVER['REQUEST_METHOD']) && isset($_POST['deliveryMethod'])) {
        // someone sent over an invalid
        if(!Readability::hasValidParams())
        {
            Readability::logMessage("ERROR:Someone tried to send a request with an invalid set of parameters.");
            die();
        }

        require_once 'Zend/Validate/EmailAddress.php';

        //FILTER DATA
        $bodyContent    = $_POST['bodyContent'];
		$deliveryMethod = $filters->filter($_POST['deliveryMethod']);
		$username       = $filters->filter($_POST['username']);
        $key            = $filters->filter($_POST['key']);
        $pageUrl        = $filters->filter($_POST['pageUrl']);
        $pageTitle      = $filters->filter($_POST['pageTitle']);

        if(!Readability::validateSecureKey($key))
        {
            $errors[] = 'key';
            Readability::logMessage("ERROR:Someone tried to send an email with an invalid key.");
        }

        // VALIDATE DATA

        $emailValidator = new Zend_Validate_EmailAddress();

        // NO ERRORS SEND EMAIL
        if(count($errors) == 0)
        {
            // store the from address so it's saved for future use
            setcookie("username", $username, time()+3600*24*7*4, "/");
            setcookie("deliveryMethod", $deliveryMethod, time()+3600*24*7*4, "/");

            require_once 'Zend/Mail.php';
            require_once 'Zend/Mail/Transport/Smtp.php';

            $mailer = new Zend_Mail_Transport_Smtp('smtp.googlemail.com', Array(
                'auth'      => 'login',
                'username'  => 'readability@arc90.com',
                'password'  => '**********',
                'ssl'       => 'ssl',
                'port'      => 465,
            ));
            $mailer->EOL = "\r\n";    // gmail is fussy about this
            Zend_Mail::setDefaultTransport($mailer);

            $mail = new Zend_Mail();
            $mail->setBodyText("This is a document sent by Readability from Arc90 to your Kindle.");
            $mail->setFrom('readability@arc90.com', 'Readability');
            $mail->addHeader('Reply-To', 'readability@arc90.com');

			$at              = $mail->createAttachment($bodyContent);
//			$at->type        = 'text/html';
			$at->type        = 'text/html; charset=UTF-8; name="readability.htm"';
			$at->filename    = ($pageTitle != "" ? $pageTitle : 'readability') . '.htm';

			if($deliveryMethod == "wireless") {
				$mail->addTo('chrisd@arc90.com');
				$mail->addTo($username . "@kindle.com");
			} else {
				$mail->addTo($username . "@free.kindle.com");
			}

            $mail->setSubject("Sent via Readability: {$pageTitle}");

            try
            {
                if(!$mail->send())
                {
                    Readability::logMessage("ERROR:There was an error sending to kindle. POST: " . print_r($_POST, true));
                }
                else
                {
                    $page = 'complete';
                }
            }
            catch(Exception $e)
            {
                Readability::logMessage("ERROR:There was an exception sending the email. POST: " . print_r($_POST, true));
                Readability::logMessage("ERROR:".$e->getMessage());
            }

            //header('location: close.html');
        }
    } // end of: if method == POST
    elseif('get' == strtolower($_SERVER['REQUEST_METHOD']))
    {
        $_SESSION['secureKey'] = Readability::generateSecureKey();
    }

    class Readability
    {
        public static function isError($field, $errors)
        {
            if(in_array($field, $errors))
            {
                return TRUE;
            }
            return FALSE;
        }

        public static function getErrorClass($field, $errors)
        {
            if(in_array($field, $errors))
            {
                return 'class = "error"';
            }
            return '';
        }

        public static function getParam($param)
        {
            if(isset($_POST) && array_key_exists($param, $_POST))
            {
                return $_POST[$param];
            }
            elseif(isset($_COOKIE) && array_key_exists($param, $_COOKIE))
            {
                return $_COOKIE[$param];
            }
            return '';
        }

        public static function logMessage($message)
        {
			echo $message;
            $logFile = dirname(__FILE__) . '/log.txt';

            $handle = @fopen($logFile, 'a');
            if(is_resource($handle))
            {
                $message = date('Y-m-d G:i:s') . ' :: ' . $message . "\n";
                fwrite($handle, $message);
                fclose($handle);
            }
        }

        public static function generateSecureKey($length = 8)
        {
            $sucureKey = "";
            $possible = "012*3456)789b(cdfg#hjkmn@pqrs!tvwx[yz";

            for($x=0; $x < $length; $x++)
            {
                $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);

                if (!strstr($sucureKey, $char))
                {
                    $sucureKey .= $char;
                }
            }
            return $sucureKey;
        }

        /**
         * this adds a small (very small) level of security
         *
         * @param string $testKey 
         * @return void
         * @author David Hauenstein
         */
        public static function validateSecureKey($testKey)
        {
            if(!array_key_exists('secureKey', $_SESSION))
            {
                $_SESSION['secureKey'] = self::generateSecureKey();
                return false;
            }
            else
            {
                if($testKey != $_SESSION['secureKey'])
                {
                    return false;
                }
            }
            return true;
        }

        public static function emailAsLinks($addresses)
        {
            $toReturn = '';
            foreach($addresses as $address)
            {
                $toReturn .= '<a href="mailto:'.$address.'">' . $address . '</a>, ';
            }
            return substr($toReturn, 0, strlen($toReturn)-2);
        }

        public static function hasValidParams()
        {
            $requiredParams = array('bodyContent', 'deliveryMethod', 'username', 'key', 'pageTitle', 'pageUrl');
            $sentParams = array_keys($_POST);
            foreach($requiredParams as $required)
            {
                if(!in_array($required, $sentParams))
                {
                    return false;
                }
            }
            return true;
        }
    }
?>
<?= '<?' ?>xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title>Readability</title>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
        <script type="text/javascript" charset="utf-8">
			$(function() {
                $('#cancel-kindle').click(function(){
                    window.location = 'http://localhost/readability/close.html';
                    return false;
                });

                $('#send-kindle').click(function(){
                    document.getElementById('send-kindle-form').submit();
                    return false;
                });

				$('input[name="deliveryMethod"]').click(function() {
					if(this.value == "wireless") {
						$('#kindleDomain').text('@kindle.com');
					} else {
						$('#kindleDomain').text('@free.kindle.com');
					}
				});

	            <?php if($page == "complete"){ ?>
	            timer = setTimeout(function(){
	                window.location = 'close.html';
	            }, 3000);
	            <?php } ?>
            });
        </script>
        <style type="text/css" media="screen">
            *{
                margin: 0;
            }
            #kindle-container{
                font-size: 14px;
                margin: 0;
                padding: 0;
                width: 500px;
                height: 490px;
                font-family: times, serif;
                background-color: #fff;
            }
            h2{
                margin: 0 0 10px;
				/*
                background: url(http://lab.arc90.com/experiments/readability/images/email-head.gif) #e2e3e4 no-repeat 15px center;
                text-indent: -99999px;
				*/
                height: 40px;
            }
			fieldset {
				margin: 10px;
			}
			fieldset h3 {
				text-align: center;
				font-variant: small-caps;
			}
            p.error{
                color: #c33;
                font-size: 14px;
            }
            .helper{
                font-size: 12px;
                margin-top: 3px;
                color: #666;
            }
            .details{
                font-style: italic;
                font-size: 15px;
            }
            .helper,
            .details{
                margin-left: 120px; /* add label width + label padding-right */
            }
            .section{
				text-align: center;
                margin-top: 15px;
                clear: both;
            }
			label {
				padding: 5px;
				display: block;
			}
            #note{
                <?php if(count($errors) > 0){ ?>
                height: 100px;
                <?php } else { ?>
                height: 140px;
                <?php } ?>
            }
            #send-kindle,
            #cancel-kindle{
                padding: 2px 2px;
                font-family: times, serif;
                background-color: #e7e8e9;
                font-size: 17px;
                border: solid 2px #666;
                cursor: pointer;
            }
            #send-kindle{
                font-weight: bold;
            }
            #cancel-kindle{
                margin-left: 10px;
            }
            .logo{
                position: absolute;
                left: 10px;
                bottom: 10px;
            }
            #complete{
                margin-top: 120px;
                text-align: center;
                padding: 20px;
            }
            #complete p{
                margin: 0 0 10px;
                font-size: 16px;
            }
            #complete img{
                margin-top: 100px;
            }
        </style>
    </head>
    <body>
        <div id="kindle-container">
            <h2>Send to Kindle</h2>
			<p>Readability will deliver this document to your Kindle so you may read it at your leisure.</p>
			
            <?php if($page == 'form'){ ?>
            <form action="./kindle.php" method="post" accept-charset="utf-8" id="send-kindle-form">

				<fieldset id="deliveryMethod">
					<h3>How shall we deliver your document?</h3>
					<div style="margin: 0 auto; width: 300px;">
					<label for="deliveryMethod-wireless"><input type="radio" name="deliveryMethod" value="wireless" id="deliveryMethod-wireless" /> Wirelessly, please. (15&cent;/MB Amazon Fee)</label>
					<label for="deliveryMethod-email"><input type="radio" name="deliveryMethod" value="email" id="deliveryMethod-email" /> Email is fine. (Free)</label>
					</div>
				</fieldset>
				
				<fieldset id="kindleUsername" style="text-align: center">
					<h3>Please enter your Amazon Kindle username.</h3>
					<input type="text" name="username" value="" id="username" /><span id="kindleDomain">@kindle.com</span>
				</fieldset>
				
                <div class="section">
                    <button id="send-kindle">Send to Kindle</button>
                    <button id="cancel-kindle">Cancel</button>
                </div>

				<div class="section">
					<em>Please ensure readability@arc90.com is approved to send email to your kindle address. <a href="http://www.amazon.com/manageyourkindle">You can manage approved emails here.</a></em>
				</div>
                <img src="http://lab.arc90.com/experiments/readability/images/email-readability.gif" alt="Readability" class="logo" />
                <input type="hidden" name="pageUrl" value="<?= htmlspecialchars($pageUrl); ?>" id="pageUrl" />
				<input type="hidden" name="bodyContent" value="<?= htmlspecialchars($bodyContent) ?>" />
                <input type="hidden" name="pageTitle" value="<?= htmlspecialchars($pageTitle); ?>" id="pageTitle" />
                <input type="hidden" name="key" value="<?= htmlspecialchars($_SESSION['secureKey']); ?>" id="key" />
            </form>
            <?php }else if($page == "complete"){ ?>
            <div id="complete">
                <p>
                    A link to this page has been sent to <?php echo Readability::kindleAsLinks($to) ?>
                </p>
                <p>
                    Thanks for using Readability.
                </p>
                <div>
                    <img src="http://localhost/readability/images/footer-thanks.png" alt="Readability" />
                </div>
            </div>
            <?php } ?>
        </div>
    </body>
</html>
