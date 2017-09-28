<?php
/*
 * @author Rannk
 * @Date 2006-06-08
 */
class PageOp {

	var $Page;
	var $CRecord;
	var $PageSize;
	var $link_where;
	
	function PageOp($Page,$CRecord,$PageSize,$link_where){
        $page = ceil($Page);
        if($page == 0) $page = 1;
		$this->Page     = $page;
		$this->CRecord  = $CRecord;
		$this->PageSize = $PageSize;
		$this->link_where = $link_where;
	}

	function Multi($num, $perpage, $curpage, $mpurl, $maxpages = 0,$where = "") {
		$multipage = '';
		if ($num > $perpage) {
			$page = 10;
			$offset = 4;

			$realpages = ceil($num / $perpage);
			$pages = $maxpages && $maxpages < $realpages ? $maxpages : $realpages;
			
			if($curpage>$pages) $curpage = $pages;

			$from = $curpage - $offset;
			$to = $curpage + $page - $offset -1;
			if ($page > $pages) {
				$from = 1;
				$to = $pages;
			} else {
				if ($from < 1) {
					$to = $curpage +1 - $from;
					$from = 1;
					if (($to - $from) < $page && ($to - $from) < $pages) {
						$to = $page;
					}
				}
				elseif ($to > $pages) {
					$from = $curpage - $pages + $to;
					$to = $pages;
					if (($to - $from) < $page && ($to - $from) < $pages) {
						$from = $pages - $page +1;
					}
				}
			}

            $multipage .= '<ul class="pagination"><li';
            if($curpage == 1) {
                $multipage .= " class='disabled'" . '><a href="#" aria-label="Next"><span aria-hidden="true">&laquo;</span></a></li>';;
            }else {
                $multipage .= '><a href="javascript:pagechange(1)" aria-label="Next"><span aria-hidden="true">&laquo;</span></a></li>';
            }


			for ($i = $from; $i <= $to; $i++) {
				$multipage .= $i == $curpage ? '<li class="active"><a href="javascript:pagechange('.$i.')" >' . $i . '</a></li>' : '<li><a href="javascript:pagechange('.$i.')" >' . $i . '</a></li>';
			}

            $multipage .= "<li";
            if($curpage == $pages) {
                $multipage .= " class='disabled'";
            }
			$multipage .= '><a href="javascript:pagechange('.($i+1).')" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li></ul>';
		}
		return $multipage;
	}
	
	function PageShow(){
	    if($this->CRecord > 0){
			$form_con  = '<form name="spilt_page_form" id="spilt_page_form" action="'.$_SERVER["REQUEST_URI"].'" method="post">';
			$form_con .= '<input type="hidden" name="page">';
			
			if($this->link_where != ""){
				$where = explode("&",$this->link_where);
				foreach($where as $values){
				    $in = explode("=",$values);
					if($in[0] != ""){
					    $form_con .= '<input type="hidden" name="'.$in[0].'" value="'.urldecode($in[1]).'">';
					}
				}
			}
			
			$form_con .= "</form>";
			
			$form_con .= "<script language='javascript'>\n";
			$form_con .= "function pagechange(pg){ \n";
			$form_con .= "if(pg>0){ document.getElementById('spilt_page_form').page.value=pg; document.getElementById('spilt_page_form').submit();}}</script>";

			$multi = $this  ->  Multi($this->CRecord,$this->PageSize,$this->Page,"");
			return  "<nav>".$multi. $form_con."</nav>";
	    }else{
	    	return "";
	    }
	}
}
?>