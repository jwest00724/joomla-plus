<?php

/*******************************************************************
* This file is a generic interface to Aliro, Joomla 1.5+, Joomla 1.0.x and Mambo
* Copyright (c) 2008-10 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://acmsapi.org
* To contact Martin Brampton, write to martin@remository.com
*
*/

abstract class cmsapiUserPage {
	protected $cname = '';
	protected $prefix = 'cmsapi';
	protected $baseurl = '';
	protected $itemcount = 0;
	protected $itemsperpage = 10;
	protected $startItem = 1;
	protected $currentpage = 1;
	protected $pagetotal = 1;
	protected $itemid = 1;
	protected $countshown = false;
	protected $interface = null;

	function __construct ($cname, $itemcount, $itemsperpage, $page, $querystring, $prefix='cmsapi') {
		$this->cname = $cname;
		$this->prefix = $prefix;
		$this->interface = cmsapiInterface::getInstance($this->cname);
		$this->baseurl = "index.php?option=$this->cname";
		if ('Aliro' != _CMSAPI_CMS_BASE) {
			$this->itemid = $this->interface->getCurrentItemid();
			$this->baseurl .= "&Itemid={$this->itemid}";
		}
		$this->baseurl .= $querystring;
		$this->itemcount = $itemcount;
		$this->itemsperpage = max(1,$itemsperpage);
		$this->startItem = 1;
		$this->finishItem = $itemsperpage;
		$this->pagetotal = ceil($this->itemcount/$this->itemsperpage);
		$this->setPage($page);
	}

	// CMS Specific code
	protected function T_ ($string) {
		return $string;
	}

	protected function setPage ($currentpage) {
		$this->currentpage = $currentpage;
		$basecount = ($currentpage - 1) * $this->itemsperpage;
		$this->startItem = $basecount;
	}

	public function pageTitle ($page, $special=null) {
		echo 'title="';
		if ($special) echo $special;
		else echo $this->T_('Show results').' ';
		$finish = $page * $this->itemsperpage;
		$start = $finish - $this->itemsperpage + 1;
		if ($finish > $this->itemcount) $finish = $this->itemcount;
		printf ($this->T_('%s to %s of %s'), $start, $finish, $this->itemcount).'"';
	}

	private function makePageLink ($number) {
		return $number > 1 ? $this->baseurl.'&page='.$number : $this->baseurl;
	}

	// Custom code for Nucleus Research - pass remositoryClassificationHandler::getInstance()
	public function showPageCount ($handler) {
		$choices = array (5, 10, 25);
		$radios = '';
		$pagecount = $handler->getPageCount();

		foreach ($choices as $choice) {
			if ($choice == $pagecount) $checked = 'checked="checked"';
			else $checked = '';
			$radios .= <<<RADIO_BUTTON

		<input type="radio" name="pagecount" id="pagecount$choice" value="$choice" $checked onclick="document.{$this->prefix}perpage.submit();" />
		<label for="pagecount$choice">$choice</label>
RADIO_BUTTON;

			$checked = '';
		}

		return <<<PAGE_COUNT

		<div class="{$this->prefix}pagecount">
		<form action="index.php" method="post" name="{$this->prefix}perpage">
		<strong>Results per page:&nbsp;</strong>
		$radios
		<input type="hidden" name="option" value="$this->cname" />
		<input type="hidden" name="func" value="search" />
		<input type="hidden" name="Itemid" value="$this->itemid" />
		</form>
		<!-- End of {$this->prefix}pagecount -->
		</div>

PAGE_COUNT;

	}

	public function showNavigation ($pagespread) {
		if ($this->itemcount <= $this->itemsperpage) return;
		$lowpage = max(1,intval($this->currentpage - ($pagespread+1)/2));
		$highpage = $lowpage + $pagespread;
		if ($highpage > $this->pagetotal) {
			$lowpage = max(1, $lowpage - ($highpage-$this->pagetotal));
			$highpage = $this->pagetotal;
		}
		$previous = $this->currentpage - 1;
		if ($previous) {
			$url = $this->interface->sefRelToAbs($this->makePageLink($previous));
			$prevtext = $this->T_('Prev');
			$previouslink = <<<PREVIOUS_LINK
			<a href="$url">$prevtext</a>
PREVIOUS_LINK;
			$url = $this->interface->sefRelToAbs($this->baseurl);
			$startlink = <<<START_LINK
			<a href="$url">&laquo;</a>
START_LINK;
		}
		else $previouslink = $startlink = '';
		$page = $lowpage;
		if ($page > 1) $navdetails = '...';
		else $navdetails = '';
		$spacer = '';
		while ($page <= $highpage) {
			if ($page == $this->currentpage) {
				$navdetails .= $spacer.$page;
			}
			else {
				$url = $this->interface->sefRelToAbs ($this->makePageLink($page));
				$navdetails .= <<<NAV_DETAIL
				<a href="$url">$page</a>
NAV_DETAIL;
			}
			$spacer = ' ';
			$page++;
		}

		if ($page <= $this->pagetotal) $navdetails .= '...';
		$next = $this->currentpage + 1;

		if ($next <= $this->pagetotal) {
			$url = $this->interface->sefRelToAbs($this->makePageLink($next));
			$nexttext = $this->T_('Next');
			$nextlink = <<<NEXT_LINK
			<a href="$url">$nexttext</a>
NEXT_LINK;
			$url = $this->interface->sefRelToAbs($this->makePageLink($this->pagetotal));
			$lastlink = <<<LAST_LINK
			<a href="$url">&raquo;</a>
LAST_LINK;
		}
		else $nextlink = $lastlink = '';

		$pagetext = $this->T_('Page');
		if (!$this->countshown) {
			// $count_control = $this->showPageCount();
			// If used, add $count_control after first div below
			$this->countshown = true;
			return <<<BIG_NAVIGATION

			<div class="{$this->prefix}pagecontrols">
			<div class='{$this->prefix}pagenav'>
				<strong>$pagetext:&nbsp;</strong>
				$startlink $previouslink $navdetails $nextlink $lastlink
			<!-- End of {$this->prefix}pagenav -->
			</div>
			<div class="{$this->prefix}pagecontrolsend"></div>
			<!-- End of {$this->prefix}pagecontrols -->
			</div>

BIG_NAVIGATION;

		}
		else return <<<NAVIGATION

		<div class="{$this->prefix}filelistingfooter">
		<div class='{$this->prefix}pagenav'>
			<strong>$pagetext:&nbsp;</strong>
			$startlink $previouslink $navdetails $nextlink $lastlink
		<!-- End of {$this->prefix}pagenav -->
		</div>
		</div>

NAVIGATION;

	}

	// Custom code for Nucleus Research
	public function showItemSummary () {
		$summary = sprintf('<p>Displaying %s-%s results of <strong>%s search results</strong></p>', $this->startItem+1, min($this->startItem+$this->itemsperpage,$this->itemcount), $this->itemcount);
		echo <<<SUMMARY

		<div>
			$summary
		</div>

SUMMARY;

	}

	public function startItem () {
		return $this->startItem;
	}

	public function itemsPerPage () {
		return $this->itemsperpage;
	}
}