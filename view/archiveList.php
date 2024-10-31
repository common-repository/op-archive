<?php
/**
 * @package OrangePants
 * @subpackage Archive
 *
 * All functionality called from within plugindir/op-archive/view/adminPanel, inheriting the objects from that file
 */
?>

<div id="op_listArchives">
	<h3>List of archives</h3>
	<ul>
		<?php foreach( $this->archiveList() as $dateDir => $fileList ) { ?>
			<li class="op_archiveDirectory">
				<span><?php echo $dateDir; ?></span>
				<ul>
				<?php foreach ( $fileList as $file ) {?>
					<li><a href="<?php echo $file; ?>"><?php echo basename( $file ); ?></a></li>
				<?php } ?>
				</ul>
			</li>
		<?php } ?>
	</ul>
</div>