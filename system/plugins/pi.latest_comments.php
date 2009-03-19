<?php
/*
=====================================================
 Latest Comments
 by: Cody Lundquist
 http://meanstudios.com
=============================================================
 This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
 To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/3.0/
 or send a letter to Creative Commons, 171 Second Street, Suite 300,
 San Francisco, California, 94105, USA.
=============================================================
	File:			pi.latest_comments.php
-------------------------------------------------------------
	Compatibility:	ExpressionEngine 1.6+
-------------------------------------------------------------
	Purpose:		Pulls the entries with at least one comment from the specified channel(s) and sorts by latest comment.
=============================================================
*/


$plugin_info = array(
						'pi_name'			=> 'Latest Comments',
						'pi_version'		=> '1.0.1',
						'pi_author'			=> 'Cody Lundquist',
						'pi_author_url'		=> 'http://meanstudios.com/',
						'pi_description'	=> 'Pulls the entries with at least one comment from the specified channel(s) and sorts by latest comment.',
						'pi_usage'			=> Latest_comments::usage()
					);

class Latest_comments {

    var $return_data;


    /** ----------------------------------------
    /**  Latest Comments
    /** ----------------------------------------*/

	function Latest_comments()
	{
		global $TMPL, $DB, $FNS, $LOC;

		/* Grab Template Data */
		$tagdata = $TMPL->tagdata;
        $retdata = "";

		/* Get tag parameters */
        $limit = ( ! $TMPL->fetch_param('limit')) ? '10' : $TMPL->fetch_param('limit');
        $sort = ( ! $TMPL->fetch_param('sort')) ? 'DESC' : $TMPL->fetch_param('sort');

        /* Do some SQL Magic */
        $sql = "SELECT a.comment_date, a.name, a.comment_id, b.title, b.url_title, c.comment_url
				FROM exp_comments a, exp_weblog_titles b, exp_weblogs c
				WHERE ";
        if ($weblog = $TMPL->fetch_param('weblog'))
        {
            $xql = "SELECT weblog_id FROM exp_weblogs WHERE ";

            $str = $FNS->sql_andor_string($weblog, 'blog_name');

            if (substr($str, 0, 3) == 'AND')
                $str = substr($str, 3);

            $xql .= $str;

            $query = $DB->query($xql);

            if ($query->num_rows == 0)
            {
                return '';
            }
            else
            {
                if ($query->num_rows == 1)
                {
                    $sql .= "a.weblog_id = '".$query->row['weblog_id']."' ";
                }
                else
                {
                    $sql .= "(";

                    foreach ($query->result as $row)
                    {
                        $sql .= "a.weblog_id = '".$row['weblog_id']."' OR ";
                    }

                    $sql = substr($sql, 0, - 3);

                    $sql .= ") ";
                }
            }
        }

        $sql .= "AND a.comment_date = b.recent_comment_date
                 AND a.weblog_id = c.weblog_id
				 ORDER BY a.comment_date $sort
				 LIMIT $limit";

        $query = $DB->query($sql);

        /* Replace the variables */
		if ($query->num_rows > 0)
        {
            foreach($query->result as $row)
            {
                $temp = str_replace( array(LD.'author'.RD,
										   LD.'time_passed'.RD,
                                           LD.'title'.RD,
										   LD.'url_title'.RD,
										   LD.'comment_url_title_auto_path'.RD,
										   LD.'comment_id'.RD),
                                     array($row['name'],
                                           ($this->_duration($LOC->now - $row['comment_date'])),
                                           $row['title'],
										   $row['url_title'],
										   $row['comment_url'].$row['url_title'].'/',
										   $row['comment_id']), $tagdata);
                $retdata .= $temp;
            }
        }

		$this->return_data = $retdata;
	}

	/* Simple duration function */
	function _duration($secs)
	{
		$vals = array(' weeks' => (int) ($secs / 86400 / 7),
					  ' days' => $secs / 86400 % 7,
					  ' hours' => $secs / 3600 % 24,
					  ' minutes' => $secs / 60 % 60,
					  ' seconds' => $secs % 60);

		$ret = array();

		$added = false;
		foreach ($vals as $k => $v) {
			if ($v > 0 || $added) {
				$added = true;
				$ret[] = $v . $k;
			}
		}

		return join(' ', $ret);
	}

// ----------------------------------------
//  Plugin Usage
// ----------------------------------------

// This function describes how the plugin is used.

function usage()
{
ob_start();
?>
  :: Required Parameters ::
    weblog - Which weblogs it should pull the entries from.
    - weblog="blog|blog2|blog3"

  :: Optional Parameters ::
    limit - How many entries it should pull. It defaults to 20 if blank.
    - limit = "15"
    sort - How you want the entries to sort. It defaults to Descending if left blank.
    - sort = "asc"

  :: Variables ::
  {title} - Title of entry
  {url_title} - The URL Title of entry
  {author} - Author of latest comment on the entry.
  {time_passed} - The time since the latest comment on the entry.  It will look something like 1 week 2 days 4 hours 30 seconds.
  {comment_url_title_auto_path} - If you have the Comment Page URL option set in your Weblog Preferences Path Settings this will print out the URL to the comment page.
  {comment_id} - This is the ID of your comment for use along with anchor tags.

  :: Usage Example::
  {exp:latest_comments weblog="blog"}
  <p>{time_passed} ago<br />{author} commented on <a href="{comment_url_title_auto_path}#{comment_id}">{title}</a></p>
  {/exp:latest_comments}

<?php
$buffer = ob_get_contents();

ob_end_clean();

return $buffer;
}
/* END */

}
// END CLASS
?>