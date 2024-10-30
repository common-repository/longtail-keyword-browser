<?php

$node_list = array();

$keyword = trim(strtolower($_REQUEST['keyword']));

if ($keyword)
{
	$list = $this->getLongtailTree($keyword, $_REQUEST['country']);

	foreach ($list as $s_keyword)
	{
		$state = 'leaf';
		if ($s_keyword['has_children'])
		{
			$state = 'closed';
		}

		$node = array(
			'data'	=> $s_keyword['keyword'],
			'state'	=> $state,
			'attr'	=> array(
				'rank'		=> $s_keyword['rank']
			)
		);

		$node_list[] = $node;
	}

	usort($node_list, 'sortRank');
}

exit(json_encode($node_list));

function sortRank($a, $b)
{
	if ($a['attr']['rank'] == $b['attr']['rank'])
	{
		return 0;
	}

	return ($a['attr']['rank'] < $b['attr']['rank']) ? -1 : 1;
}