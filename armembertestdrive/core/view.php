<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');
global $wpdb;
$orphanTables = array();
$allTables = $wpdb->get_col("SHOW TABLES");
$sites = $wpdb->get_col("SELECT blog_id FROM " . $wpdb->prefix . "blogs");

foreach ($allTables as $tableName) {
	if (! preg_match('/^' . $wpdb->prefix . '([0-9]+)[_](.+)/', $tableName)) continue;
	if (!in_array( get_number_from_table_name($tableName) , $sites)) {
		array_push($orphanTables, array('Orphaned Table Name' => $tableName));
	}
}

echo "<br/> <h4> Total Tables: ".count($orphanTables)."</h4><br/>";

function get_number_from_table_name($tableName) {
	global $wpdb;
	$noPrefix = preg_replace('/^' . $wpdb->prefix . '/', '', $tableName);
	return (int)substr($noPrefix, 0, strpos($noPrefix, '_'));
}

if( isset($_REQUEST['delete_garbage_table']) && $_REQUEST['delete_garbage_table'] == 'Delete' ){
	global $wpdb;

	if( is_array($orphanTables) && !empty($orphanTables) && count($orphanTables) > 0 ){
		foreach( $orphanTables as $orphan_table_name){
			$tablename = $orphan_table_name['Orphaned Table Name'];
			
			$wpdb->query("DROP TABLE IF EXISTS ".$tablename);
		}
		echo "<script>window.location.reload();</script>";
	}

}

?>
<br/>
<form method="POST" action="">
	<table border="1" cellpadding="5" cellspacing="5">
		<?php
			if( is_array($orphanTables) && !empty($orphanTables) && count($orphanTables) > 0 ){
				$total_tables = count($orphanTables);
				$x = 1;
				foreach( $orphanTables as $orphan_table_name){
					if( $x == 1){
						echo "<tr>";
					}
				?>
					<td> <?php echo $orphan_table_name['Orphaned Table Name']; ?></td>
				<?php
					if( $x % 6 == 0 && $x != $total_tables){
						echo "</tr>";
						echo "<tr>";
					} else if( $x == $total_tables ){
						echo "</tr>";
					}
					$x++;
				}
			} else {
			?>
			<tr>
				<th colspan="6"> No tables to remove </th>
			</tr>
			<?php
			}
		?>
		<tr>
			<th colspan="6"><input type="submit" name="delete_garbage_table" value="Delete" /></th>
		</tr>
	</table>
</form>