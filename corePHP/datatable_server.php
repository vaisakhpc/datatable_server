<?php
/**
 * CorePHP
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CorePHP
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CorePHP Datatable Helpers
 *
 * @package		CorePHP
 * @subpackage	Helpers
 * @category	Helpers
 * @author		Vaisakh P C
 * @link		https://github.com/vaisakhpc/datatable_server
 */
require_once("./MysqlInfo.php");
// ------------------------------------------------------------------------
if ( ! function_exists('dt_get_json'))
{
	/**
	 * Datatable server is php code that you can create json easily for ajax datatable.
	 * git : https://github.com/vaisakhpc/datatable_server
	 *
	 *
	 *
     * $config=array(
     *
     * Coloums you need to select form datatabse. [Array()]
     * 'aColumns' => array( 'id','name','email','mobile'),
	 *
     * Index coloum of the table.	[String]
     * 'sIndexColumn' => 'id',
	 *
     * Table name 	[String]
 	 * You can also gave join statment
 	 * ex : 'sTable' =>"order left join invoice on invoiceid=orderid" ,
     * 'sTable' =>"addressbook" ,
	 *
     * If you have any coditional statment you can add this. [String][optional]
     * 'sCondition'=>'name like "%a"',
	 *
	 * Output needed in each coloum. [Array(Array('type'=>value))]
	 * Usage
	 * text 	=>	Normal Text values
	 * var     =>  Variable Name.The varaible name will replaced with the variable value.
	 * 				The variable that you are selecting should be added in aColoums.
	 * html    =>  You can directly gave html. If you want to use variable in betwwen you can use {{variablename}}.
	 * eval    =>  You can call a function that you have written.You can use the arguments as your variable that you selected in aColoums.
     * 'aColumns_out' => array(
     *    array('var'=>'id'),
     *    array('var'=>'name',
     *    array('html'=>'<a href="mailto:{{email}}">{{email}}</a>'),
     *    array('eval'=>'your_function($mobile)'),
     *    ),
     *  );
     *
     * @access	public
	 * @param	array()
	 * @return	json 	JSON data for datatable
	 */
	function dt_get_json($config,$arg=array())
	{
		/* creating instance */]
		/* Create a class named MysqlInfo with the code to create and set connection */
		$mysql = new MysqlInfo();
		$CI = $mysql->getConnection();

		/* Array of database columns which should be read and sent back to DataTables. Use a space where
		 * you want to insert a non-database field (for example a counter or static image)
		*/
		$aColumns = $config['aColumns'];

		/* Indexed column (used for fast and accurate table cardinality) */
		$sIndexColumn = $config['sIndexColumn'];

		/* DB table to use */
		$sTable = $config['sTable'];

		/* Query condition  */
		$sCondition=isset($config['sCondition'])?$config['sCondition']:'';

		$sGroupBy=isset($config['sGroupBy'])?$config['sGroupBy']:'';

		$smysqlf=isset($config['smysqlf'])?$config['smysqlf']:0;

		$sDistinct=(isset($config['smysqlf']) && $config['smysqlf'] == 1)?" distinct ":"";

		/*
		* Paging
		*/
		$sLimit = "";
		if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
		{
			$sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
				intval( $_GET['iDisplayLength'] );
		}


		/*
		* Ordering
		*/
		$sOrder = "";
		if ( isset( $_GET['iSortCol_0'] ) && isset($_GET['iSortingCols']) )
		{
			$sOrder = "ORDER BY  ";
			for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
			{
				if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
				{
					$ordercolumn = $aColumns[ intval( $_GET['iSortCol_'.$i] ) ];
					if (strpos($ordercolumn, ' as ') !== false) {
						$ordercolumn = strstr($ordercolumn, ' as ', true);
						$sOrder .= $ordercolumn." ".($_GET['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
					}
					else
					{
						$sOrder .= "`".$ordercolumn."` ".($_GET['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
					}
				}
			}

			$sOrder = substr_replace( $sOrder, "", -2 );
			if ( $sOrder == "ORDER BY" )
			{
				$sOrder = "";
			}
		}

		/*
		 * Filtering
		 * NOTE this does not match the built-in DataTables filtering which does it
		 * word by word on any field. It's possible to do here, but concerned about efficiency
		 * on very large tables, and MySQL's regex functionality is very limited
		*/
		$sWhere = "";
		if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
		{
			$sWhere = "WHERE (";
			for ( $i=0 ; $i<count($aColumns) ; $i++ )
			{
				if (strpos($aColumns[$i], ' as ') !== false) {
					$wh = strstr($aColumns[$i], ' as ', true);
					if (!$sGroupBy) {
						$sWhere .= "".$wh ." LIKE '%".$_GET['sSearch']."%' OR ";
					}
				}
				else if(strpos($aColumns[$i], '`') !== false)
				{
					$sWhere .= $aColumns[$i]." LIKE '%".$_GET['sSearch']."%' OR ";
				}
				else{
						$sWhere .= "`".$aColumns[$i]."` LIKE '%".$_GET['sSearch']."%' OR ";
				}
			}
			$sWhere = substr_replace( $sWhere, "", -3 );
			$sWhere .= ')';
		}
		/* Individual column filtering */
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
			{
				if ( $sWhere == "" )
				{
					$sWhere = "WHERE ";
				}
				else
				{
					$sWhere .= " AND ";
				}
				if (strpos($aColumns[$i], ' as ') !== false) {
					$wh = strstr($aColumns[$i], ' as ', true);
					$sWhere .= "".$wh ." LIKE '%".$_GET['sSearch_'.$i]."%' ";
				}
				else
				{
					$sWhere .= "`".$aColumns[$i]."` LIKE '%".$_GET['sSearch_'.$i]."%' ";
				}
			}
		}

		if($sWhere==""&&$sCondition!="")
			$sWhere.='where ('.$sCondition.') ';
		else if($sCondition!="")
			$sWhere.='and ('.$sCondition.') ';

		if ($sGroupBy) {
			$sGroup = " group by ".$sGroupBy;
		} else {
			$sGroup = "";
		}

		/*
		 * SQL queries
		 * Get data to display
		*/
		if(!$smysqlf)
		{
		$sQuery = "
			SELECT ".$sDistinct."SQL_CALC_FOUND_ROWS `".str_replace(" , ", " ", implode("`, `", $aColumns))."`
			FROM   $sTable
			$sWhere
			$sGroup
			$sOrder
			$sLimit
			";	
		}
		else
		{
		$sQuery = "
			SELECT ".$sDistinct."SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $aColumns))."
			FROM   $sTable
			$sWhere
			$sGroup
			$sOrder
			$sLimit
			";
		}
		//echo $sQuery;exit;
		/* fetching result from database */
		$rResult = $CI->query($sQuery);
		$sQuery = "
			SELECT ".$sDistinct."COUNT(distinct `".$sIndexColumn."`) as 'count'
			FROM $sTable $sWhere
		";
		$rResultTotal = $CI->query($sQuery);
		$rResultTotal = $rResultTotal->fetch_assoc();
		$iTotal = $rResultTotal["count"];
		$iFilteredTotal=$iTotal;

		/*
		 * Output
		 */
		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iFilteredTotal,
			"aaData" => array()
		);
		foreach ($rResult->fetch_all(MYSQLI_ASSOC) as  $aRow )
		{
				$row = array();
				extract($aRow);

				foreach($config['aColumns_out'] as $aColumnsrow)
				{
					$col="";
					foreach($aColumnsrow as $key=>$value)
					{
						if($key=='text')
							$col.=$value;
						else if($key=='var')
							$col.=$aRow[$value];
						else if($key=='html')
						{
							if(preg_match_all('/\{{(.*?)\}}/',$value,$match)){
								foreach($match[1] as $v=>$k)
									$match[1][$v]=$aRow[$k];
										$col.=str_replace($match[0],$match[1],$value);
							}
							else
								$col.$value;
						}
						else if($key=='eval')
							eval("\$col = ".$value.";");
						else
							$col.='Invalid type!';
					}
					$row[] = $col;
				}

				$output['aaData'][] = $row;
		}

		if(!empty($arg))
		{
			$output['aaData'][] = $arg;
		}
			return json_encode( $output );

	}
}

// ------------------------------------------------------------------------



/* End of file datatable_helper.php */
/* Location: ./application/helpers/datatable_helper.php */
