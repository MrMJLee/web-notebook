
<?php
require_once 'Note.php';

ob_start();
session_start();
date_default_timezone_set('Pacific/Auckland');

$title = 'My Notebook';
$rightHeading = 'Previous Entries';
$rightContent = '';
$outline = '';
$topic = '';
$mainContent = false;
$insert_edit='add';
$insert_value='Save';
$this_script = $_SERVER['SCRIPT_NAME'];
$login_msg ='Log In';
$status_page ='false';
if (isset($_REQUEST['cmd'])){  # If there's a 'cmd' field, use it
	$cmd = $_REQUEST['cmd'];
	
} else {
	$cmd = 'display';           # otherwise default action is 'display'
}

/**
 * @param Note[] $results
 * @param string $this_script
 * @return string
 */
function FrontPage($results, $this_script)
{
    $editIcon = '<span class="glyphicon glyphicon-edit"></span>';
    $deleteIcon = '<span class="glyphicon glyphicon-trash"></span>';
    $showIcon = '<span class="glyphicon glyphicon-eye-open"></span>';
	$output = '';
	if ($results)
	{
	    $output .= '<div class="list-group">';
    
		foreach ($results as $row)
		{
		    $topic = $row->topic;
		    $lastUpdated = $row->getFormattedDate();
		    $id = $row->id;
		    
			$output .= '<div class="list-group-item">'
	               . '<h4 class="list-group-item-heading">' 
                   .  toLink($this_script."?cmd=show&id=$id", $topic) . "</h4>"
                   . '<p class="list-group-item-text">' 
                   .  "$lastUpdated"
                   . toLink($this_script."?cmd=show&id=$id", "Show $showIcon", "pull-right note-link");
			
			if (checkLoggedIn()) {
			    $output .= toLink($this_script."?cmd=edit&id=$id", "Edit $editIcon", "pull-right note-link")
                        . toLink($this_script."?cmd=del&id=$id", "Delete $deleteIcon", "pull-right note-link", "Are you sure you want to delete $topic?");
			}	   
			
            $output .= "</p>"
                   . "</div>";
		}
		$output .= '</div>';
	} else {
	    $output = "<i>No results</i>";
	}
	return $output;
}
function status(){
	global $title;
	global $mainContent;
	global $status_page;
	$status_page = true;
	$title = 'Status Page';
	$mainContent = "<div>
					<p>This 'Notebook' allows a user to 'Add', 'Edit', 'Delete', 'Search' and basic 'Show' for your notes.</p>
					<p>Login is required to add, edit and delete entries.</p>
					<p>This 'Notebook' allows a user to add entries with containing single & double quotes.</p>
					<p>This 'Notebook' doesn't provide the 'Show' option to render the markup correctly OR an RSS feed.</p>
					<p>This 'Notebook' is hosted on a virtual machine using Amazon EC2 (Amazon Elastic Compute Cloud service) to run it online.<p>
					<p>This 'Notebook' is also connected to an EC2 auto-scaling group. This will create minimum number of instances of 1 and a maximum number of 
					instances of 2 to an Elastic load balancer.<p>
					<p>The load balancer manages traffic across 'Notebook' server in the cloud service.<p></div>";
					
					
	
}
function insert()
{	
	if (isset($_POST["add"])) 
	{ // did I press the submit button?
		// add the note
		$topic = $_POST['topic'];
		$outline = $_POST['outline'];
		if(!empty($topic))
		{   
			$note = Note::createNewNote($topic , $outline);
			$note->save();
			return true;
		}
	
		return false;
	}
	return false;

}

function update($id)
{
    echo $_POST["update"];
    if (isset($_POST["update"])) {
		$note = Note::find($id);
		$note->topic = $_POST['topic'];
		$note->outline = $_POST['outline'];
		var_dump($note);
		return $note->save();
    }
    return false;
}

function search($terms)
{
	if(isset($_POST['search']))
	{
		$results = Note::search($terms);
		return $results;
	}
}
function deleteNote($id)
{
	$note = Note::find($id);
	return $note->delete();
}

function td ($item)
{
	return "<td>$item</td>\n";
}

function checkLoggedIn()
{
    return isset($_SESSION['valid']) && $_SESSION['valid'];
}

function login()
{
	global $login_msg;
	$logged = false;
	if (isset($_POST['login']) && !empty($_POST['username'])&& !empty($_POST['password'])) {     
	    if ($_POST['username'] == "Assignment2" && $_POST['password'] == "159352" ) {
			$_SESSION['valid'] = true;
			$_SESSION['timeout'] = time();
			$_SESSION['username'] = $user;
			$logged = true;
		}
	}
	return $logged;
}

/**
 * @param string $url
 * @param string $whatToDisplay
 * @param string $class
 * @param boolean $confirm
 * @return string
 */
function toLink ($url, $whatToDisplay, $class=false, $confirm=false)
{
	return "<a href='$url'"
	 . ($class ? " class='$class'" : "")
	 . ($confirm ? ' onclick="return confirm(' . "'$confirm'" . ')"' : "")
	 . ">$whatToDisplay</a>";
}

function toLinkRight ($url, $whatToDisplay)
{
    return "<a href='$url' class='pull-right note-link'>$whatToDisplay</a>";
}
//onclick="return confirm('Are you sure?')"
$redirect = false;
$id =  isset($_GET['id']) ? $_GET['id'] : false;

function stayOnLogPage()
{
	echo '<script type="text/javascript">';
	echo "$('#loginModal').modal('show');";
	echo "</script>";
	#echo '<script type="text/javascript">';
	#echo "$(document).ready(function() {";
	#echo "$('#loginModal').modal('show');";
	#echo "});";
	#echo "</script>";
}

switch ($cmd) {
    case 'login':
        $ok = login();
        $redirect=true;
       #if($ok=true){
        #	if(checkLoggedIn())
        #	{
        #	$redirect = true;
        #	}
        #}
        #else
        #{
        #	stayOnLogPage();
        #}'''
        break;
    case 'logout':
        $_SESSION['valid'] = false;
        $redirect = true;
        break;
    case 'update':
        $redirect = true;
        if (checkLoggedIn()) {
            update($_POST['id']);
        }
        break;
	case 'add':
	    $redirect = true;
	    if (checkLoggedIn()) {  	
		  	insert(); // redirect home on successful insert
	    }
		break;
	case 'del':
	    $redirect = true;
	    if (checkLoggedIn()) {
	        deleteNote($id); // redirect on successful delete
	    }
		break;
	case 'show':
	    $results = Note::findAll();
	    $rightContent = FrontPage($results, $this_script);  # Here they both call Frontpage
	    $note = Note::find($id);
	    $title = $note->topic;
	    $time ="Last Modified: ".$note->getFormattedDate();
	    $mainContent = $note->outline; // TODO markdown
	    break;
	case 'edit':
	    if (checkLoggedIn()) {
	        $note = Note::find($id);
	        $insert_edit = 'update';
	        $insert_value = "Edit";
	        $title = 'Edit Note';
	        $outline = $note->outline;
	        $topic = $note->topic;
	    }
	
	case 'display':
		$results = Note::findAll();
		$rightContent = FrontPage($results, $this_script);  # Here they both call Frontpage
		break;
	case 'f':
	    $terms =  $_POST['find'];
		$results = search($terms);
		$rightHeading = "Search Results for: $terms";
		$rightContent = FrontPage($results, $this_script);  # Here they both call Frontpage
		break;
	default:
		$redirect = false;
		break;
}

if($redirect){
	header("Location: $this_script");
}
?>

<!DOCTYPE html>
	<html>
		<head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">

			<title>Notebook</title>
			<style type="text/css">
			body {
				padding-top: 50px;
			}
			.note-link {
		        padding-left: 5px;
			}
			#title {
				font-family:  Verdana, Helvetica, sans-serif;
			}
			.footer {
 				display: table-row;
  				height: 100px;
			}

			</style>
				<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
			    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
			    <script src="js/bootstrap.min.js"></script>
		</head>
		<body>
        <div id="loginModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        	<div class="modal-dialog modal-sm">
   		    	<div class="modal-content">
        			<div class="modal-header">
        				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        				<h4 class="modal-title"><?=$login_msg?></h4>
        			</div>
            		<div class="modal-body">
    					<form class="form" method='POST' action="<?=$this_script?>">
        				 	<input type='hidden' name='cmd' value='login'>
        				  	<div class="form-group">
                                   <input type ="text" class="form-control" name='username' placeholder="Enter username" >
							</div>
							<div class="form-group">
                                   <input type ="password" class="form-control" name='password' placeholder="Enter password" >
							</div>
        				 	<div class="form-inline pull-right">
        				 	       <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                   <input type="submit" name='login' value="Log in" class="btn btn-primary">
        				 	</div>
        				 	<div class="clearfix"></div>
        				</form>
            		</div>
            	</div>
        	</div>
		</div>
		
			<div class="navbar navbar-inverse navbar-fixed-top">
		 	<div class="container">
		    	<div class="navbar-header">
		      		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
		        <span class="icon-bar"></span>
		        <span class="icon-bar"></span>
		        <span class="icon-bar"></span>
		      </button>
		      <a class="navbar-brand" href="<?=$this_script?>">Notebook</a>
		      <a class="navbar-brand" href="?status">Status</a>	
		    </div>
		    <?php if (isset($_GET['status'])) status();?>
		    <div class="collapse navbar-collapse">
		    <ul class="nav navbar-nav">
		    <li><a href="http://validator.w3.org/check/referer" rel="nofollow" title="Validate as HTML5"><span class="glyphicon glyphicon-link"></span> HTML5</a></li>
			<li><a href="http://jigsaw.w3.org/css-validator/validator?uri=<?php echo $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]; ?>" rel="nofollow" title="Validate as CSS3"><span class="glyphicon glyphicon-link"></span> CSS3</a></li>
		    </ul>
		    <?php if(!checkLoggedIn()):?>
		      <ul class="nav navbar-nav navbar-right">
      			<li><a href="#loginModal" data-target="#loginModal" data-toggle="modal"><span class="glyphicon glyphicon-user"></span> Log In</a></li>
      		  </ul>
      		<?php else: ?>
      			<ul class="nav navbar-nav navbar-right" >
      				<li><?= toLink($this_script."?cmd=logout", '<span class="glyphicon glyphicon-log-out"></span>'.' Logout')?></li>
      			</ul>
      		<?php endif; ?>
		    </div>
		  </div>
		</div>

		<div class="container">
		  <div class="notebook">
		    <div class="row">
				<div class="col-sm-12 hidden-sm hidden-xs">
					<h2 class="page-header" id="title"><?= $title ?></h2>
		      	</div>
		  		<div class="col-sm-6 col-md-7">
          			<div class="visible-sm visible-xs">
          			<h3><?= $title ?></h3>
          			</div>
          			<?php if($cmd=='show'):?>
          				<?= $mainContent ?><br/><br/>
						<span class='glyphicon glyphicon-step-backward'><?=toLink($this_script, 'Back to Homepage');?></span>
          				<div class="navbar-fixed-bottom row-fluid">
						  <div class="navbar-inner">
						    <div class="container">
						      <?=$time?>
						    </div>
						  </div>
						</div>
					<?php elseif ($status_page===true):?>
						<?= $mainContent?><br/><br/>
						<span class='glyphicon glyphicon-step-backward'><?=toLink($this_script, 'Back to Homepage');?></span>
          			<?php else: ?>
    	      		<form method='POST' action="<?=$this_script?>" onSubmit="return checkTopic()">
						<?php if ($cmd==='edit'): ?>
						<input type='hidden' name='id' value="<?=$id?>">
						<?php endif; ?>
						<input type='hidden' name='cmd' value="<?=$insert_edit?>">
						<div class = 'form-group field-notebook-topic'>
							<label class='control-label' for='notebook-topic'>Topic</label>
							<input type='text' id='notebook-topic' class='form-control' name='topic' placeholder='Enter topic..' value="<?= $topic?>">
						</div>
						<div class="form-group field-notebook-outline required validating">
							<label class='control-label' for='notebook-outline'>Outlines</label>
							<textarea id='notebook-outline' class='form-control' name='outline' rows='6' placeholder='Enter your thoughts...'><?= $outline ?></textarea>
							<p class='help-block help-block-error'></p>
						</div>
						<input id='btn' type='submit' name="<?=$insert_edit?>" value="<?= $insert_value?>" class="btn btn-primary pull-right">
						<script>
							function checkTopic(){
								topic = document.getElementsByName('topic')[0].value;
								if(topic==''){
									alert('Please enter the topic');
									return false
								}
							}
						</script>
						<?php if(!checkLoggedIn()):?>
						<h4 class="text-warning text-right">Please log in to add or edit entries.&nbsp;&nbsp;&nbsp;</h4>
						<script>
							document.getElementById("btn").disabled = true; 
						</script>
						<?php endif;?>
						<?php if ($cmd==='edit'): ?>
						<span class='glyphicon glyphicon-step-backward'><?=toLink($this_script, 'Back to Homepage');?></span>
						<?php endif; ?>
						<div class="clearfix"></div>
					</form>
					<?php endif; ?>
          		</div>
          		<div class="col-sm-6 col-md-5">
          		<h3><?= $rightHeading?></h3>
          		<?php if ($cmd==='f') {
          		    echo toLink($this_script, 'Reset search');

          		}
                ?>          		
          		
				 <form class="form" method='POST' action="<?=$this_script?>">
				 	<div class="form-group">
				 		<input type='hidden' name='cmd' value='f'>
				  	</div>
				  	<div class="form-group">
		  	            <div class = "input-group">               
                           <input type = "text" class="form-control" name='find' placeholder="Enter search term...">
							<span class = "input-group-btn">
                            	<input type="submit" name='search' value="Search" class="btn btn-default pull-right">
                           </span>
                        </div>
				 	</div>
				</form>
			 	<?=$rightContent?>		
 			</div>
 		</div>
	</div>
</div>		
</body>
</html>

