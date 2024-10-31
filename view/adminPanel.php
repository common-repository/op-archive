<?php
/**
 * @package OrangePants
 * @subpackage Archive
 *
 * All functionality called from within class OP_Archive, inheriting the $this variable
 */
?>
<div class="wrap">
	<h2>OrangePants: Archive</h2>
	<form method="post" id="op_archive_form">
		<?php wp_nonce_field('op_archive_form');?>
		<div class="archiveButton">
			<input type='submit' name='op_archive_backup' value='Backup Now!'/>
		</div>
		<hr />
		<?php //include_once( dirname(__FILE__) ."/options.php" ); ?>
<!--		<hr />-->
		<?php include_once( dirname(__FILE__) ."/archiveList.php" ); ?>
	</form>
</div>