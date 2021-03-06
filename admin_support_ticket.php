<?php
$adminpage = "admin_support_ticket.php";
@include_once("header.php");
@include_once("function.php");
@include_once("account_function.php");
@include_once("alert_function.php");
if (empty($_GET['id'])) ForceDie();	
$_GET['id'] = trim((@$_GET['id']));
if (!is_numeric($_GET['id'])) ForceDie();
$result_tickets = mysql_query("select * FROM tickets WHERE ID=".$_GET['id']." OR TicketID=".$_GET['id']." ORDER BY `Opened` ASC ");
if (@mysql_num_rows($result_tickets) == 0) ForceDie();
$row_ticket = mysql_fetch_array($result_tickets);
if (!empty($row_ticket['TicketID'])) ForceDie();
if (@$_POST['action'] == 'update')
{
	$description = (@$_POST['content']);
	$err = array();
	if (@$_POST['adminaction'] == 'reply' || @$_POST['adminaction'] == 'replyandreturn' || @$_POST['adminaction'] == 'replyandclose')
	{
		if ($description == '')
			$err[]=$lang['content_required'];
	} elseif(empty($_POST['adminaction'])) {
	if ($description == '')
		$err[]=$lang['content_required'];
	if ($row_ticket['Status'] == "CLOSED")
		$err[]=$lang['ticket_has_been_closed'];
	}
	if(!count($err))
	{
		if ($_POST['adminaction'] == 'close'){
			mysql_query("UPDATE tickets SET Status='CLOSED', ClosedOn=NOW(), ClosedBy=".$_SESSION['UserID']." WHERE ID=".$_GET['id']);
			$_SESSION['msg']['alert-success']=$lang['close_success'];
			header("Location: admin_support_ticket.php?id=".$_GET['id']);
			exit; 
		} elseif ($_POST['adminaction'] == 'delete') {
			mysql_query("DELETE FROM tickets WHERE ID=".$_GET['id']." OR TicketID=".$_GET['id']);
			$_SESSION['msg']['alert-success']=$lang['delete_success'];
			header("Location: admin_support.php");
			exit; 
		} else {
			$description = nl2br($description);
			mysql_query("	INSERT INTO tickets(TicketID,Description,Opened,OpenedBy,LastUpdated,LastUpdatedBy)
							VALUES(
								".$_GET['id'].",
								".check_input($description).",
								NOW(),
								".$_SESSION['UserID'].",
								NOW(),
								".$_SESSION['UserID']."
							)");
			$TicketID = mysql_insert_id();
			if ($_POST['adminaction'] == 'replyandclose')
			{
				mysql_query("UPDATE tickets SET Status='CLOSED', ClosedOn=NOW(), ClosedBy=".$_SESSION['UserID']." WHERE ID=".$_GET['id']);
				$_SESSION['msg']['alert-success']=$lang['reply_close_success'];
			} else {
				mysql_query("UPDATE tickets SET LastUpdated=NOW(), LastUpdatedBy=".$_SESSION['UserID']." WHERE ID=".$_GET['id']);
				$_SESSION['msg']['alert-success']=$lang['reply_success'];
			}
			if ($_POST['adminaction'] == 'replyandreturn')
			{
				header("Location: admin_support.php");
			} else {
				header("Location: admin_support_ticket.php?id=".$_GET['id']."#".$TicketID);
			}
			exit; 
		}
	}
	SetErrAlert($err);
}
?>
<div id="page">
<p class='breadcrumb'><a href='admin_support.php'><?php echo $lang['support']; ?></a> &raquo; <strong><?php echo $row_ticket['Summary']; ?></strong></p>
<?php EchoAlert(); ?>
<form name="ticket_update" id="ticket_update" action="" method="post" onsubmit="">
<table class="list">
	<tr>
		<th colspan="5">[<?php echo $row_ticket['ID']; ?>] <?php echo $row_ticket['Summary']; ?></th>
	</tr>
	<tr class="list_head">
		<td><?php echo $lang['status']; ?></td>
		<td nowrap><?php echo $lang['opened']; ?></td>
		<td nowrap><?php echo $lang['last_updated']; ?></td>
		<td nowrap><?php echo $lang['closed']; ?></td>
		<td nowrap><?php echo $lang['regarding']; ?></td>
	</tr>
	<tr class="list_entry">
		<td><?php 
		echo $row_ticket['Status'];
		$ticket_closed = ($row_ticket['Status']=='CLOSED');
		?></td>
		<td>
			<?php echo timediff2(strtotime($row_ticket['Opened']),strtotime(date("Y-m-d g:i:s a")),$lang).' '.$lang['by']." <strong>".UserID2UserName($row_ticket['OpenedBy'])."</strong>"; ?>
		</td>
		<td>
			<?php echo timediff2(strtotime($row_ticket['LastUpdated']),strtotime(date("Y-m-d g:i:s a")),$lang).' '.$lang['by']." <strong>".UserID2UserName($row_ticket['LastUpdatedBy'])."</strong>"; ?>
		</td>
		<td>
				<?php
				if (!empty($row_ticket['ClosedOn']))
					echo timediff2(strtotime($row_ticket['ClosedOn']),strtotime(date("Y-m-d g:i:s a")),$lang).' '.$lang['by']." <strong>".UserID2UserName($row_ticket['ClosedBy'])."</strong>";
				else
					echo '-';
				?>
		</td>
		<td>
			<?php
			if (!empty($row_ticket['RegardingURL']))
				echo '<a href="'.$row_ticket['RegardingURL'].'">';
			echo $row_ticket['Regarding'];
			if (!empty($row_ticket['RegardingURL']))
				echo '</a>';
			?><br />
		</td>
	</tr>
</table>
<table class="list">
	<tr class="list_head">
		<td colspan="2">&nbsp;</td>
	</tr>
<?php
mysql_data_seek($result_tickets,0);
while($row_ticket = mysql_fetch_array($result_tickets)) {
?>
		<tr class="list_entry<?php if (@++$i%2==0) echo "_alt"; ?>">
			<td class="support_left" valign="top">
				<?php if(!empty($_SERVER['HTTPS'])) { ?>
				<img class="support_avatar" src="https://secure.gravatar.com/avatar/<?php echo md5(UserID2Email($row_ticket['OpenedBy'])); ?>" align="middle" />
				<?php } else { ?>
				<img class="support_avatar" src="http://1.gravatar.com/avatar/<?php echo md5(UserID2Email($row_ticket['OpenedBy'])); ?>" align="middle" />
				<?php } ?>
				<br>
				<strong><?php echo UserID2UserName($row_ticket['OpenedBy']); ?></strong>
				<br>
				<span class="hint"><?php echo timediff2(strtotime($row_ticket['Opened']),strtotime(date("Y-m-d g:i:s a")),$lang); ?></span>
			</td>
			<td><?php
			// echo str_replace("\n", '<br />', $row_ticket['Description']);
			echo get_magic_quotes_gpc()?stripslashes($row_ticket['Description']):$row_ticket['Description'];
			?></td>
		</tr>
<?php } ?>
		<tr class="list_entry">
			<td class="support_left"><?php echo $lang['add_update']; ?></td>
			<td>
			<textarea name="content" style="width:500px;height:100px;"></textarea>
			</td>
		</tr>
		<tr class="list_entry">
			<td width="200"></td>
			<td colspan="3">
				<input type="radio" name="adminaction" id="reply" value="reply" checked="checked" /><label for="reply"><?php echo $lang['reply']; ?></label>
				<input type="radio" name="adminaction" id="replyandreturn" value="replyandreturn" /><label for="replyandreturn"><?php echo $lang['reply_and_return']; ?></label>
				<input type="radio" name="adminaction" id="replyandclose" value="replyandclose" /><label for="replyandclose"><?php echo $lang['reply_and_close']; ?></label>
				<input type="radio" name="adminaction" id="close" value="close" /><label for="close"><?php echo $lang['close']; ?></label>
				<input type="radio" name="adminaction" id="delete" value="delete" /><label for="delete"><?php echo $lang['delete']; ?></label>
				<input type="hidden" name="action" value="update" />
				<input class="button" type="submit" value="<?php echo $lang['add_update']; ?>">
			</td>
		</tr>
</table>
</form>
</div>
<?php @include_once("footer.php") ?>