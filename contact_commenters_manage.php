<?php 
/*
Author: Ajith Prasad Edassery
URL   : http://www.DollarShower.com/
RSS   : http://feeds2.feedburner.com/DollarShower
Notes : Please feel free to modify and use this piece of code as you wish. 
*/

	// Set Default options once
	if (get_option('ContactCommentersInitialized') != '1') {
		update_option('mail-sender', 'Your Name Here');
		update_option('mail-sender-email', 'You@YourBlog.com');
		update_option('mail-subject', 'Thank you for completing 100 comments!');
		update_option('mail-ind', '1');
		update_option('mail-content', 'Mail content goes here');
		update_option('ContactCommentersInitialized', '1');
	}


	// Globals
	global $wpdb;


	// Post processing
	$stepsubmitted = $_POST['step'];
	$commoption = $_POST['commoption'];
	$postid = $_POST['postid'];
	$datefrom = $_POST['datefrom'];
	$dateto = $_POST['dateto'];
	$actdays = $_POST['actdays'];
	$topdays = $_POST['topdays'];
	$newdays = $_POST['newdays'];
	$quickmailids = $_POST['quickmailids'];

	// First page: Fill posts combo
	if ($stepsubmitted == "") {
		if ($commoption == "")
			 $commoption = "NEW";

		if ($datefrom == "")
			$datefrom == "FROM DATE";

		if ($dateto == "")
			$dateto == "TO DATE";

		$sql = "SELECT ID, post_title from $wpdb->posts WHERE post_status = 'publish' AND post_type='post' AND post_date >= (
DATE_SUB(curdate(),INTERVAL 90 day)) ORDER BY post_title";
		$psts = $wpdb->get_results($sql);

		$postselopts = "";

		foreach ($psts as $pst) {
			$postselopts .= "<option value=\"$pst->ID\"" . (($postid == $pst->ID) ? "selected" : "") . ">$pst->post_title</option>\n";
		}

	} elseif ($stepsubmitted == '1') {
		$emailselopts = "";
		$sql = ""; 

		// Fill the email combo
		switch ($commoption) {
			case "ALL":
				$sql = "SELECT DISTINCT comment_author_email, comment_author from $wpdb->comments WHERE comment_type NOT IN ('pingback','trackback')
AND comment_approved = '1' AND comment_author_email <> '' GROUP BY comment_author_email ORDER BY comment_author";
				break;

			case "ACT":
				$sql = "SELECT DISTINCT comment_author_email, comment_author from $wpdb->comments WHERE comment_type NOT IN ('pingback','trackback')
AND comment_approved = '1' AND comment_author_email <> '' AND comment_date <= (
DATE_SUB(curdate(),INTERVAL $actdays day)) AND comment_author_email NOT IN (SELECT DISTINCT comment_author_email from $wpdb->comments WHERE comment_approved = '1' AND comment_date > (
DATE_SUB(curdate(),INTERVAL $actdays day)) ) GROUP BY comment_author_email ORDER BY comment_author";
				break;

			case "DAT":
				if ($datefrom == "" || strlen($datefrom) != 10) $datefrom = "01/01/1998";
				if ($dateto == "" || strlen($dateto) != 10) $dateto = date("m/d/Y");
				$sql = "SELECT DISTINCT comment_author_email, comment_author from $wpdb->comments WHERE comment_type NOT IN ('pingback','trackback')
AND comment_approved = '1' AND comment_author_email <> '' AND comment_date >= STR_TO_DATE('$datefrom', '%m/%d/%Y') AND comment_date <= STR_TO_DATE('$dateto', '%m/%d/%Y') GROUP BY comment_author_email ORDER BY comment_author";
				break;
			
			case "PST":
				$sql = "SELECT DISTINCT comment_author_email, comment_author from $wpdb->comments WHERE comment_type NOT IN ('pingback','trackback')
AND comment_approved = '1' AND comment_author_email <> '' AND comment_post_ID = '$postid' GROUP BY comment_author_email ORDER BY comment_author";
				break;

			case "TOP":
				$topdaysstr = "";
				if ($topdays != 0) $topdaysstr = "AND comment_date > (
DATE_SUB(curdate(),INTERVAL $topdays day))";
				$sql = "SELECT DISTINCT comment_author_email, COUNT(*) cnt, comment_author from $wpdb->comments WHERE comment_author_email <> '' AND comment_approved = '1' AND comment_type NOT IN ('pingback','trackback')" . $topdaysstr . " GROUP BY comment_author_email ORDER BY 2 DESC";
				break;

			case "NEW":
				$sql = "SELECT DISTINCT comment_author_email, comment_author from $wpdb->comments WHERE comment_type NOT IN ('pingback','trackback')
AND comment_approved = '1' AND comment_author_email <> '' AND comment_date >= (
DATE_SUB(curdate(),INTERVAL $newdays day)) AND comment_author_email NOT IN (SELECT DISTINCT comment_author_email from $wpdb->comments WHERE comment_approved = '1' AND comment_date < (
DATE_SUB(curdate(),INTERVAL $newdays day)) ) GROUP BY comment_author_email ORDER BY comment_date DESC";
				break;

			case "QUI":
				break;

			default:
				$sql = "SELECT DISTINCT comment_author_email, comment_author from $wpdb->comments WHERE comment_author_email <> ''GROUP BY comment_author_email  ORDER BY comment_author_email LIMIT 100";
				break;
		}

		$comments = $wpdb->get_results($sql);
		$totalemails = count($comments);

		$topcount = 0;

		if ($commoption != "QUI") {
			foreach ($comments as $comment) {
				if ($topcount == 10) {
					$totalemails = 10;
					break;
				}

				if ($commoption == "TOP") {
					$topcount = $topcount + 1;
					$emailselopts .= "<option value=\"$comment->comment_author~*~$comment->comment_author_email\">$comment->comment_author &lt;$comment->comment_author_email&gt; ($comment->cnt) </option>\n";
				} else {
					$emailselopts .= "<option value=\"$comment->comment_author~*~$comment->comment_author_email\">$comment->comment_author &lt;$comment->comment_author_email&gt;</option>\n";
				}
			}
		} else {
			$totalemails = 0;
			$qemailids = explode(",", $quickmailids);
			foreach ($qemailids as $qemailid) {
				$qemailid = str_replace(" ", "", $qemailid);
				if ($qemailid != "") {
					$emailselopts .= "<option value=\"$qemailid~*~$qemailid\">$qemailid</option>\n";				
					$totalemails++;
				}
			}
		}
	} elseif ($stepsubmitted == '2') {
		// Update last mail options
		update_option('mail-sender', stripslashes($_POST['mail_sender']));
		update_option('mail-sender-email', $_POST['mail_sender_email']);
		update_option('mail-subject', stripslashes($_POST['mail_subject']));
		update_option('mail-content', stripslashes($_POST['mail_text']));
		update_option('mail-ind', $_POST['mail_individ']);

		echo("<div class=\"wrap\"><h2>Contact Commenters</h2>");
		echo("<font color=orange>Sending mails to selected commenters... Please be patient and DO NOT close this window or click the 'Back' button!</font>");

		// extract email addresses and names
		$sendeeslist = explode("^*^", $_POST['mail_ads']);

		// Number of recepients. if it is more than 25 and individual mail option is NOT set we actually
		// send 25 BCC addresses per mail. For example 40 addresses mean a 25 BCC mail and another 15 BCC mail


		$emailcount = count($sendeeslist) - 1;
		$emailtemp = stripslashes($_POST['mail_text']);
		$emailsubtemp = stripslashes($_POST['mail_subject']);
		$emailindi = $_POST['mail_individ'];
		$emailheaders = "MIME-Version: 1.0\r\n" . "From: " . stripslashes($_POST['mail_sender']) . " <" . $_POST['mail_sender_email'] . ">\r\n";
		$emailbccheader = "";
		$ccsenderror = 0;
		$bccbatchcount = 0;
		$bcctotalcount = 0;

		foreach ($sendeeslist as $sendee) {
			$name_email = explode("~*~", $sendee);

			// email address, name of the sendee
			$emailstr = next($name_email);
			$namestr = prev($name_email);

			if ($emailstr != "") {
				// content salutation changed if individual mails are send
				if (1 == $emailindi) {
					$contentstr = str_replace("[NAME]", $namestr, $emailtemp);
					$substr = str_replace("[NAME]", $namestr, $emailsubtemp);

					if (FALSE == mail($emailstr, $substr, $contentstr, $emailheaders)) {
						echo("<br/><br/><font color=red><strong>Error while sending email(s). Please make sure that your server is set up to handle PHP mails!</strong></font>");
						$ccsenderror = 1;
						break;
					}
				}
				else {
					// Remove the place holder, just in case
					if (0 == $bcctotalcount) {
						$emailtemp = str_replace("[NAME]", "", $emailtemp);
						$emailsubtemp = str_replace("[NAME]", "", $emailsubtemp);
					}

					if (0 == $bccbatchcount)
						$emailbccheader = "BCC: " . $emailstr;
					else
						$emailbccheader = $emailbccheader . ", "  . $emailstr;
						
					$bccbatchcount ++;

					// Send mail if 25 addresses have been BCCed
					if ($bccbatchcount == 25 || ($emailcount == $bcctotalcount + $bccbatchcount)) {
						$emailbccheader = $emailbccheader . "\r\n";

						if (FALSE == mail($_POST['mail_sender_email'], $emailsubtemp, $emailtemp, $emailheaders . $emailbccheader)) {
							echo("<br/><br/><font color=red><strong>Error while sending email(s). Please make sure that your server is set up to handle PHP mails!</strong></font>");
							$ccsenderror = 1;
							break;
						} else {
							$bcctotalcount += $bccbatchcount;
							$bccbatchcount = 0;
						}
					}
				}
			}
		}

		echo("</div>");
	}
	// End Post processing
?>


<!-- Styles -->
<style type="text/css">
fieldset {
	margin:20px 0; 
	border:1px solid #cecece;
	padding:15px;
}

fieldset input {
	// height: 12px;
	vertical-align: middle;
	font-size: 11px;
}

fieldset select {
	height: 22px;
	width: 310px;
	font-size: 11px;
}
</style>
<!-- End Styles -->


<!-- Scripts -->
<link rel="stylesheet" href="http://dev.jquery.com/view/tags/ui/latest/themes/flora/flora.datepicker.css" type="text/css" media="screen" title="Flora (Default)">
<script src="http://dev.jquery.com/view/tags/ui/latest/ui/ui.datepicker.js"></script>

<script type="text/javascript">
var $cc = jQuery.noConflict();
 
$cc('document').ready(function() 
{	
	$cc('#datef').datepicker();
	$cc('#datet').datepicker();
});


function onStep1SelChange() {
	for (var i=0; i < document.step1.commopt.length; i++)
		if (document.step1.commopt[i].checked)
			document.step1.commoption.value = document.step1.commopt[i].value;
}

// Cleanup any javascript with 'with'

function onStep1DropdownClick() {
	with (document.step1) {
		commopt[3].checked = true;
		datef.value = datet.value = datefrom.value = dateto.value = "";
		commoption.value = "PST";
	}
}

function onStep1DropdownTopClick() {
	document.step1.commopt[4].checked = true;
	document.step1.commoption.value = "TOP";
}

function onStep1DropdownActClick() {
	document.step1.commopt[1].checked = true;
	document.step1.commoption.value = "ACT";
}

function onStep1DropdownNewClick() {
	document.step1.commopt[5].checked = true;
	document.step1.commoption.value = "NEW";
}

function onStep1DropdownChange() {
	with (document.step1) {
		postid.value = postsel.value;
		datef.value = datet.value = datefrom.value = dateto.value = "";
	}
}

function onStep1DateClick() {
	document.step1.commopt[2].checked = true;
	document.step1.commoption.value = "DAT";
}

function onStep1QuickIDsClick() {
	document.step1.commopt[6].checked = true;
	document.step1.commoption.value = "QUI";
}

// Check the dates & Post ID
function checkStep1Submit() {
	if (document.step1.commoption.value == "PST") {
		// Check if posts are available
		if (document.step1.postsel.length == 0) {
			alert("No posts available!");
			return false;
		} else
			document.step1.postid.value = document.step1.postsel.value;

		document.step1.datef.value = document.step1.datet.value = document.step1.datefrom = document.step1.dateto = "";
	} else if (document.step1.commoption.value == "DAT") {
		// Check if dates are proper
		if (document.step1.datef.value == "" && document.step1.datet.value == "" ) {
			alert("Please select proper dates. You need to enter 'From' date or 'To' date or both!");
			return false;
		}

		// TODO: Further date validation
		document.step1.datefrom.value = document.step1.datef.value;
		document.step1.dateto.value = document.step1.datet.value;

	} else if (document.step1.commoption.value == "ACT") {
		document.step1.actdays.value = document.step1.actdayssel.value;	
	} else if (document.step1.commoption.value == "TOP") {
		document.step1.topdays.value = document.step1.topdayssel.value;
	} else if (document.step1.commoption.value == "NEW") {
		document.step1.newdays.value = document.step1.newdayssel.value;
	} else if (document.step1.commoption.value == "QUI") {
		if (document.step1.quickmailids.value == "" || !step1.quickmailids.value.match("@") || !document.step1.quickmailids.value.match(".")) {
			alert("Please enter the email IDs separated by comma");
			return false;
		}
	}

	/* return document.step1.submit(); */
	return true;
}

function backToStep1() {
	document.step2.step.value = "";
	/* return document.step2.submit(); */
	return true;
}

function removeSelectedEmailIds() {
	var i = 0;
	for (i = 0; i < document.step2.mailsel.length; i++) {
		if (document.step2.mailsel.options[i].selected) {
			document.step2.mailsel.remove(i);
			i--;
		}
	}

	document.step2.email_count.value = "Total: " + document.step2.mailsel.length + " addresses";
}

function onStep2MailIndClick() {
	document.step2.mail_individ.value = (document.step2.mail_ind.checked) ? 1 : 0;
}

function checkStep2Submit() {
	if (document.step2.mailsel.length == 0) {
		alert("No email addresses available to send mails. Please go back to 'Step 1' and select again.");
		return false;
	}

	if (document.step2.mail_sender.value.length == 0 || !document.step2.mail_sender_email.value.match("@") || !document.step2.mail_sender_email.value.match(".")) {
		alert("Please enter valid sender name and email address");
		return false;
	}
	
	if (document.step2.mail_text.value.length < 10) {
		alert("The mail content is too short. Please check again");
		return false;
	}
	
	var msg = "Send mails to " + document.step2.mailsel.length + " recepient(s) now?";
	if (!confirm(msg)) return false;

	var i = 0;
	var stremails = "";

	for (i = 0; i < document.step2.mailsel.length; i++) {
		stremails = stremails + document.step2.mailsel.options[i].value + "^*^";
	}

	document.step2.mail_ads.value = stremails;
	document.step2.mail_individ.value = (document.step2.mail_ind.checked ? 1 : 0);

	/* return document.step2.submit(); */
	return true;
}

</script>
<!-- End Scripts -->


<!-- Rendering forms based on steps -->
<?php if ($stepsubmitted == '') { ?>
<div class="wrap">
	<h2>Contact Commenters</h2>
	<font color="orange"><strong>Important:</strong> Please note that contacting commenters too often and/or without any good reason could result in spam. Please use this feature judiciously.</font>
	<form name="step1" id="step1" method="post">
	<fieldset>
		<legend><strong>Step 1. Choose an option below and click 'Step 2'</strong></legend>
		<p>
			<input type="radio" name="commopt" value="ALL" onclick="onStep1SelChange()" <?php if ($commoption == "ALL") echo("checked"); else echo(""); ?> />All commenters <strong>till date</strong> <font color="orange">(May take time and not advisable unless absolutely necessary)</font><br/>
			<input type="radio" name="commopt" value="ACT" onclick="onStep1SelChange()" <?php if ($commoption == "ACT") echo("checked"); else echo(""); ?> />Commenters who are <strong>not active</strong> for the past <select style="height:22px; width:50px;" name="actdayssel" id="actdayssel" width="10" onclick="onStep1DropdownActClick();"><option value="7">7</option><option value="10">10</option><option value="15">15</option><option value="30">30</option><option value="60">60</option><option value="90">90</option><option value="180">180</option><option value="365">365</option></select> days <font color="orange">(Those who used to actively comment before that many days)</font><br/>
			<input type="radio" name="commopt" value="DAT" onclick="onStep1SelChange()" <?php if ($commoption == "DAT") echo("checked"); else echo(""); ?> />Commenters who commented <strong>from</strong> <input class="dt" type="text" name="datef" id="datef" value="<?php echo($datefrom); ?>" size="14" maxlength="10" onclick="onStep1DateClick()" /> <strong>to</strong> <input type="text" name="datet" id="datet" value="<?php echo($dateto); ?>" size="14" maxlength="10" onclick="onStep1DateClick()" /> <font color="orange">(Date range in 'MM/DD/YYYY' format)</font><br/>
			<input type="radio" name="commopt" value="PST" onclick="onStep1SelChange()" <?php if ($commoption == "PST") echo("checked"); else echo(""); ?> />All commenters of a <strong>particular post</strong> <font color="orange">(Last 3 months' posts only)</font> <select style="height:22px;" name="postsel" id="postsel" width="60" onclick="onStep1DropdownClick()" onchange="onStep1DropdownChange()"><?php echo $postselopts; ?></select><br/>
			<input type="radio" name="commopt" value="TOP" onclick="onStep1SelChange()" <?php if ($commoption == "TOP") echo("checked"); else echo(""); ?> />Your <strong>Top 10 Commenters</strong> for the past <select style="height:22px; width:50px;" name="topdayssel" id="topdayssel" width="10" onclick="onStep1DropdownTopClick()"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="7">7</option><option value="10">10</option><option value="15">15</option><option value="30">30</option><option value="60">60</option><option value="90">90</option><option value="180">180</option><option value="365">365</option><option value="0">ALL</option></select> days <font color="orange">(Select 'ALL' to get top commenters since the blog's launch)</font><br/>
			<input type="radio" name="commopt" value="NEW" onclick="onStep1SelChange()" <?php if ($commoption == "NEW") echo("checked"); else echo(""); ?> /><strong>New Commenters</strong> in the last <select style="height:22px; width:50px;" name="newdayssel" id="newdayssel" width="10" onclick="onStep1DropdownNewClick()"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="7">7</option><option value="10">10</option><option value="15">15</option><option value="30">30</option></select> days <font color="orange">(Those who commented for the very first time in the last few days selected)</font><br/>
			<input type="radio" name="commopt" value="QUI" onclick="onStep1SelChange()" <?php if ($commoption == "QUI") echo("checked"); else echo(""); ?> />Send a <strong>Quick mail to</strong> <input class="dt" type="text" name="quickmailids" id="quickmailids" value="<?php echo($quickmailids); ?>" size="50" maxlength="250" onclick="onStep1QuickIDsClick()" /> <font color="orange">(Up to 5 email addresses comma separated)</font><br/>
		</p>
	</fieldset>
	<input type="hidden" name="step" id="step" value="1" />
	<input type="hidden" name="commoption" id="commoption" value="<?php echo($commoption); ?>" />
	<input type="hidden" name="postid" id="postid" value="<?php echo($postid); ?>" />
	<input type="hidden" name="datefrom" id="datefrom" value="<?php echo($datefrom); ?>" />
	<input type="hidden" name="dateto" id="dateto" value="<?php echo($dateto); ?>" />
	<input type="hidden" name="actdays" id="actdays" value="<?php echo($actdays); ?>" />
	<input type="hidden" name="topdays" id="topdays" value="<?php echo($topdays); ?>" />
	<input type="hidden" name="newdays" id="newdays" value="<?php echo($newdays); ?>" />
	<input type="submit" name="submit1" value="Step 2 >>" onclick="return checkStep1Submit();"/>
	</form>
</div>
<br />
<div class="wrap">
	<h2>Reminder: Have you visited these blogs lately?</h2>
	Following are the blogs owned by your last 10 commenters. It is always a good idea to visit your commenters' blogs regularly and be active there.
	<?php
	global $wpdb;

	$blogurl = str_replace("http://", "", get_bloginfo('url'));

	$sql = "SELECT DISTINCT comment_author_url from $wpdb->comments WHERE comment_author_url != '' AND comment_author_url NOT LIKE '%$blogurl%' AND comment_type NOT IN ('pingback','trackback')
AND comment_approved = '1' AND comment_author_email <> '' ORDER BY comment_date_gmt DESC LIMIT 10";
	$commurls = $wpdb->get_results($sql);
	$output = $pre_HTML;
	$output .= "\n<ul>";

	foreach ($commurls as $commurl)
		$output .= "\n<li><a href=\"$commurl->comment_author_url\" target=\"_blank\">$commurl->comment_author_url</a></li>";

	$output .="\n</ul>";
	echo ($output);
	?>

</div>

<?php } else if ($stepsubmitted == 1) {;	
?>
<div class="wrap">
	<h2>Contact Commenters</h2>
	<form method="post" name="step2" id="step2">
	<fieldset>
		<legend><strong>Step 2: Fill in the details and click 'Send Mail(s)' or go back to 'Step 1' to start all over</strong></legend>
		<p>
			<table border="0" cellpadding="0" cellspacing="0">
			<tr><td nowrap valign="top">
			<label for="mailsel">Email IDs <font color="orange">(Select & remove unwanted)</font></label><br /><select style="height:200px;width:375px;" name="mailsel" id="mailsel" size="12" multiple length="150"><?php echo($emailselopts); ?></select>&nbsp;</td><td nowrap valign="top"><br/><input style="height:22px;width:125px;" type="button" name="removesel" id="removesel" value="Remove Selected" onclick="removeSelectedEmailIds();"/><br/><input style="width:116px;" type="text" name="email_count" id="email_count" value="Total: <?php echo($totalemails); ?> addresses" size="17" disabled /></td>
			<td width="20">&nbsp;</td>
			<td nowrap valign="top">
				<label for="mail_subject">Mail Subject</label><br />
				<input type="text" name="mail_subject" id="mail_subject" size="60" maxlength="150" value="<?php echo(get_option('mail-subject')) ?>" /><br />
				<table border="0" cellpadding="0" cellspacing="0" width="200">
				<tr>
				<td nowrap align="left" width="50%">
				<label for="mail_sender">Your Name</label><br />
				<input type="text" name="mail_sender" id="mail_sender" size="28" maxlength="150" value="<?php echo(get_option('mail-sender')) ?>" /><br />
				</td>
				<td nowrap align="left" width="50%">
				<label for="mail_sender_email">Your Email</label><br />
				<input type="text" name="mail_sender_email" id="mail_sender_email" size="28" maxlength="150" value="<?php echo(get_option('mail-sender-email')) ?>" /><br />
				</td>
				</tr>
				</table>
				<label for="mail_text">Message Text</label><br/>
				<textarea style="width:380px;" name="mail_text" id="mail_text" rows="5" cols="50"><?php echo(get_option('mail-content')) ?></textarea><br />
				<input type="checkbox" name="mail_ind" id="mail_ind" <?php if (get_option('mail-ind') == '1') echo("checked"); else echo(""); ?> onclick="onStep2MailIndClick();" /> Send Individual mails <font color="orange">(Unchecked = Mail BCCed to all)</font><br />
			</td>
			</tr>
			</table>
			<strong>Note: </strong>You can use the tag '<strong>[NAME]</strong>' in your mail to salute the recepient while sending individual mails (e.g. <strong>Hello [NAME],</strong>). This tag will be replaced with the commenter's name at the time of sending every mail.
		</p>
	</fieldset>
	<input type="hidden" name="step" id="step" value="2" />
	<input type="hidden" name="commoption" id="commoption" value="<?php echo($commoption); ?>" />
	<input type="hidden" name="postid" id="postid" value="<?php echo($postid); ?>" />
	<input type="hidden" name="datefrom" id="datefrom" value="<?php echo($datefrom); ?>" />
	<input type="hidden" name="dateto" id="dateto" value="<?php echo($dateto); ?>" />
	<input type="hidden" name="mail_individ" id="mail_individ" value="<?php get_option('mail-ind'); ?>" />
	<input type="hidden" name="mail_ads" id="mail_ads" value="" />
	<input type="hidden" name="quickmailids" id="quickmailids" value="<?php echo($quickmailids); ?>" />
	<input type="submit" name="back" value="<< Step 1" onclick="return backToStep1();" />&nbsp;<input type="submit" name="submit2" value="Send Mail(s) >>" onclick="return checkStep2Submit();" />
	</form>
</div>
<br/>
<?php } else if ($stepsubmitted == 2) {
?>
<div class="wrap">
	<br/>
<?php 
	if ($ccsenderror == 0) {
		echo("Your email message was sent to $emailcount commenter(s). The mail preferences (From, Subject, Body) have been saved.<br/><br/>");
	}
?>
	<br/>
	<form method="post" name="step2" id="step2">	<!-- Intentionally kept as step2 to reuse javascript -->
	<input type="hidden" name="step" id="step" value="" />
	<input type="submit" name="back" value="Start all over" onclick="return backToStep1();" />
	</form>
	<br/>
</div>
<?php } ?>

<!-- End forms rendering -->


<div class="wrap">
	<h2>Feedback & Support</h2>
	<p>Plugin by <b>Ajith Prasad Edassery</b> | Visit the <a href="http://www.dollarshower.com/contact-commenters-wordpress-plugin/">plugin page</a> for support | <a href="http://feeds.feedburner.com/dollarshower">Subscribe to RSS feed</a> for news & updates<br />
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_donations">
	<input type="hidden" name="business" value="ajith@ajithprasad.com">
	<input type="hidden" name="item_name" value="Contact Commenters Plugin">
	<input type="hidden" name="no_shipping" value="0">
	<input type="hidden" name="no_note" value="1">
	<input type="hidden" name="currency_code" value="USD">
	<input type="hidden" name="tax" value="0">
	<input type="hidden" name="lc" value="GB">
	<input type="hidden" name="bn" value="PP-DonationsBF">
	<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
	<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1">
	</form>
	</p>
</div>
