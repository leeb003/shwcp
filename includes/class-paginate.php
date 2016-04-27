<?php
/**
 *  Simple class to create pagination routine div
**/

class paginate
{  
    private $reload;
    private $page;
    private $tpages;
    private $adjacents;
   
    // Construct
    function __construct($reload, $page, $tpages, $adjacents) {
        $this->_reload = $reload;
        $this->_page = $page;
        $this->_tpages = $tpages;
        $this->_adjacents = $adjacents;
    }

    // Get pagination div
    function getDiv () {
        $prevlabel = __( 'Previous', 'shwcp');
        $nextlabel = __('Next', 'shwcp');
        $output = "<div class='pagin'>\n";
        // previous
        if ($this->_page==1) {
            $output .= "<span>$prevlabel</span>\n";
        } elseif($this->_page==2) {
            $output .= "<a href='$this->_reload' class='prevLog'>$prevlabel</a>\n";
        } else {
			$prev_page = add_query_arg( array('pages' => $this->_page - 1) );
            $output .= "<a href='" . $prev_page . "' class='prevLog'>" 
                     . $prevlabel . "</a>\n";
        }

        // first
        if ($this->_page > ($this->_adjacents + 1)) {
            $output .= "<a href=\"" . $this->_reload . "\" class=\"logPage\">1</a>\n";
        }
    
        // interval
        if ($this->_page > ($this->_adjacents + 2)) {
            $output .= "...\n";
        }
        
        // pages
        $pmin = ($this->_page > $this->_adjacents) ? ($this->_page - $this->_adjacents) : 1;
        $pmax = ($this->_page < ($this->_tpages - $this->_adjacents)) ? ($this->_page + $this->_adjacents) : $this->_tpages;
        for ($i=$pmin; $i<=$pmax; $i++) {
            if ($i==$this->_page) {
                $output .= '<span class="current">' . $i . "</span>\n";
            } elseif ($i==1) {
                $output .= "<a href=\"" . $this->_reload . "\" class=\"logPage\">" . $i . "</a>\n";
            } else {
				$pages = add_query_arg( array('pages' => $i) );
                $output .= "<a href='" . $pages . "' class='logPage'>" . $i . "</a>\n";
            }
        }

        // interval
        if ($this->_page < ($this->_tpages - $this->_adjacents - 1)) {
            $output .= "...\n";
        }

        // last
        if ($this->_page < ($this->_tpages - $this->_adjacents)) {
			$last_page = add_query_arg( array('pages' => $this->_tpages) );
            $output .= "<a href=\"" . $last_page  . "\" class=\"logPage\">" . $this->_tpages . "</a>\n";
        }

        // next
        if ($this->_page < $this->_tpages) {
			$next_page = add_query_arg( array('pages' => $this->_page + 1) );
            $output .= "<a href='" . $next_page . "' class='nextLog'>" . $nextlabel . "</a>\n";
        } else {
            $output .= "<span>" . $nextlabel . "</span>\n";
        }

        $output .= "</div>";

        return $output;
    }
}
