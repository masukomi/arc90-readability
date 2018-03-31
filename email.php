<?php
    set_include_path( get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/lib');
    session_start();

    require_once 'Zend/Filter.php';
    require_once 'Zend/Filter/StripTags.php';
    require_once 'Zend/Filter/StringTrim.php';

    $filters = new Zend_Filter();
    $filters->addFilter(new Zend_Filter_StripTags())
            ->addFilter(new Zend_Filter_StringTrim());

    $pageTitle  = array_key_exists('pageTitle', $_GET) ? $filters->filter($_GET['pageTitle']) : '';
    $pageUrl    = array_key_exists('pageUrl', $_GET) ? $filters->filter($_GET['pageUrl']) : '';
    $page       = 'form';
    $errors     = array();

    if('post' == strtolower($_SERVER['REQUEST_METHOD']))
    {
        // someone sent over an invalid
        if(!Readability::hasValidParams())
        {
            Readability::logMessage("ERROR:Someone tried to send a request with an invalid set of parameters.");
            die();
        }

        require_once 'Zend/Validate/EmailAddress.php';

        //FILTER DATA

        $from       = $filters->filter($_POST['from']);
        $fromName   = $filters->filter($_POST['name']);
        $fromName   = !empty($fromName) ? $fromName : $from;
        $to         = $filters->filter($_POST['to']);
        $to         = array_map('trim', split(',', $to));
        $note       = $filters->filter($_POST['note']);
        $key        = $filters->filter($_POST['key']);
        $pageUrl    = $filters->filter($_POST['pageUrl']);
        $pageTitle  = $filters->filter($_POST['pageTitle']);

        if(!Readability::validateSecureKey($key))
        {
            $errors[] = 'key';
            Readability::logMessage("ERROR:Someone tried to send an email with an invalid key.");
        }

        // VALIDATE DATA

        $emailValidator = new Zend_Validate_EmailAddress();

        if(!$emailValidator->isValid($_POST['from']))
        {
            $errors[] = 'from';
        }

        if(count($to) == 0)
        {
            $errors[] = 'to';
        }
        else
        {
            foreach($to as $toAddress)
            {
                if(!$emailValidator->isValid($toAddress))
                {
                    $errors[] = 'to';
                    break;
                }
            }
        }

        // NO ERRORS SEND EMAIL
        if(count($errors) == 0)
        {
            // store the from address so it's saved for future use
            setcookie("from", $from, time()+3600*24*7*4, "/");
            setcookie("name", $fromName, time()+3600*24*7*4, "/");

            require_once 'Zend/Mail.php';
            require_once 'Zend/Mail/Transport/Smtp.php';

            $mailer = new Zend_Mail_Transport_Smtp('smtp.googlemail.com', Array(
                'auth'      => 'login',
                'username'  => 'readability@arc90.com',
                'password'  => '********',
                'ssl'       => 'ssl',
                'port'      => 465,
            ));
            $mailer->EOL = "\r\n";    // gmail is fussy about this
            Zend_Mail::setDefaultTransport($mailer);

            $body = '<html><head>';
            $body = '</head>';
            $body = '<body>';
            $body .= '<div style="font-size: 15px;">';
            $body .= '<p>This page was sent to you by: '.$from.'</p>';
            if(!empty($note))
            {
                $body .= '<p>Message from sender: </p><p><blockquote>'.stripslashes($note).'</blockquote></p>';
            }
            $body .= '<p>Just click this link: <a href="'.$pageUrl.'">'.$pageTitle.'</a></p>';
            $body .= '<hr />';
            $body .= '<p style="font-size: 90%;">Sent from <a href="http://lab.arc90.com/experiments/readability/">Readability</a> | An <a href="http://www.arc90.com">Arc90</a> lab experiment<p>';
            $body .= '</div>';
            $body .= '</body></html>';

            $mail = new Zend_Mail();
            $mail->setBodyHtml($body);
            $mail->setFrom($from, $fromName);
            $mail->addHeader('Reply-To', $from);

            foreach($to as $toAddress)
            {
                $mail->addTo($toAddress);
            }

            $mail->setSubject("Sent via Readability: {$pageTitle}");

            try
            {
                if(!$mail->send())
                {
                    Readability::logMessage("ERROR:There was an error sending the email. [to:".implode(', ', $to).", from:{$from}, notes:{$note}, pageUrl: {$pageUrl}, pageTitle: {$pageTitle}]");
                }
                else
                {
                    $page = 'complete';
                }
            }
            catch(Exception $e)
            {
                Readability::logMessage("ERROR:There was an exception sending the email. [to:".implode(', ', $to).", from:{$from}, notes:{$note}, pageUrl: {$pageUrl}, pageTitle: {$pageTitle}]");
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
            $requiredParams = array('from', 'name', 'to', 'note', 'key', 'pageTitle', 'pageUrl');
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
        <script type="text/javascript" charset="utf-8">
            window.onload = function(){
                document.getElementById('cancel-email').onclick = function(){
                    window.location = 'http://lab.arc90.com/experiments/readability/close.html';
                    return false;
                };
                document.getElementById('send-email').onclick = function(){
                    document.getElementById('send-email-form').submit();
                    return false;
                };
            };
            <?php if($page == "complete"){ ?>
            timer = setTimeout(function(){
                window.location = 'close.html';
            }, 3000);
            <?php } ?>
        </script>
        <style type="text/css" media="screen">
            *{
                margin: 0;
            }
            #email-container{
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
                background: url(http://lab.arc90.com/experiments/readability/images/email-head.gif) #e2e3e4 no-repeat 15px center;
                text-indent: -99999px;
                height: 40px;
            }
            form{
                padding-right: 20px;
            }
            label{
                font-size: 20px;
                padding-right: 10px;
                display: block;
                float: left;
                width: 130px;
                text-align: right;
            }
            input,
            textarea{
                padding: 5px;
                width: 320px;
                font-family: times, serif;
                font-size: 14px;
                border: solid 1px #999;
            }
            input.error{
                border: solid 2px #c33;
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
                margin-top: 15px;
                clear: both;
            }
            #note{
                <?php if(count($errors) > 0){ ?>
                height: 100px;
                <?php } else { ?>
                height: 140px;
                <?php } ?>
            }
            #send-email,
            #cancel-email{
                padding: 2px 2px;
                font-family: times, serif;
                background-color: #e7e8e9;
                font-size: 17px;
                border: solid 2px #666;
                cursor: pointer;
            }
            #send-email{
                margin-left: 180px;
                font-weight: bold;
            }
            #cancel-email{
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
        <div id="email-container">
            <h2>Email Page</h2>

            <?php if($page == 'form'){ ?>
            <form action="./email.php" method="post" accept-charset="utf-8" id="send-email-form">
                <div class="section">
                    <label for="name">Your Name :</label>
                    <input type="text" name="name" id="name" value="<?php echo Readability::getParam('name') ?>" />
                </div>
                <div class="section">
                    <label for="from">Your Email :</label>
                    <input type="text" name="from" id="from" value="<?php echo Readability::getParam('from') ?>" <?php echo Readability::getErrorClass('from', $errors); ?> />
                    <?php if(Readability::isError('from', $errors)){ ?>
                    <p class="helper error">
                        This field should be a valid email address.
                    </p>
                    <?php } ?>
                </div>
                <div class="section">
                    <label for="to">Recipients :</label>
                    <input type="text" name="to" id="to" value="<?php echo Readability::getParam('to') ?>" <?php echo Readability::getErrorClass('to', $errors); ?> />
                    <?php if(Readability::isError('to', $errors)){ ?>
                    <p class="helper error">
                        Please ensure that all addresses are valid email adderesses.
                    </p>
                    <?php } ?>
                    <p class="helper">
                        Separate multiple <em>email addresses</em> with commas.
                    </p>
                </div>
                <div class="section">
                    <label>Sending :</label>
                    <p class="details">
                        <?= $pageTitle ?>
                    </p>
                </div>
                <div class="section">
                    <label for="note">Note :</label>
                    <textarea name="note" id="note" rows="8" cols="40"><?php echo Readability::getParam('note') ?></textarea>
                </div>
                <div class="section">
                    <button id="send-email">Email Page</button>
                    <button id="cancel-email">Cancel</button>
                </div>
                <img src="http://lab.arc90.com/experiments/readability/images/email-readability.gif" alt="Readability" class="logo" />
                <input type="hidden" name="pageUrl" value="<?= $pageUrl; ?>" id="pageUrl" />
                <input type="hidden" name="pageTitle" value="<?= $pageTitle; ?>" id="pageTitle" />
                <input type="hidden" name="key" value="<?= $_SESSION['secureKey']; ?>" id="key" />
            </form>
            <?php }else if($page == "complete"){ ?>
            <div id="complete">
                <p>
                    A link to this page has been sent to <?php echo Readability::emailAsLinks($to) ?>
                </p>
                <p>
                    Thanks for using Readability.
                </p>
                <div>
                    <img src="http://lab.arc90.com/experiments/readability/images/footer-thanks.png" alt="Readability" />
                </div>
            </div>
            <?php } ?>
        </div>
    </body>
</html>
