<?php
/**
 * Example of an extension that handles posts from a form
 * This will be called at the very end, so any other WSForm command will already be processed (edit, create, mail, etc.)
 * if a page is created $title will hold the name of the new page
 * if a username is available, it will be in the $usrt variable
 * All defined fields in a form can be accessed through $wsPostFields or you can use the function "getFormValues(<variable>)"
 * This will be false if not available or empty
 */
ERROR_REPORTING(E_ALL);
ini_set('display_errors', 1);



include('phabricator.class.php');
$api_parameters= array();

$taskTitle = getFormValues('task-title');
$taskId = getFormValues('task-id');
$taskDescription = getFormValues('task-description');
//open, resolved, wontfix, invalid, spite
$taskstatus = strtolower( getFormValues( 'task-status' ) );
//unbreak, triage, high, normal, low, wish
$taskPriority = getFormValues('task-priority');
$taskProject = getFormValues('task-project'); // ID of project
$taskSubscriber = getFormValues('task-subscriber'); // ID('s) of subscriber(s)

if( $taskTitle !== false ) {
	$api_parameters[] =
		array(
			'type' => 'title',
			'value' => $taskTitle
		);
}

if( $taskDescription !== false ) {
	$api_parameters[] =
		array(
			'type' => 'description',
			'value' => $taskDescription
		);
}

if( $taskstatus !== false ) {
    $api_parameters[] =
        array(
            'type' => 'status',
            'value' => $taskstatus
        );
}

if( $taskPriority !== false ) {
    $api_parameters[] =
        array(
            'type' => 'priority',
            'value' => $taskPriority
        );
}

if( $taskProject !== false ) {
    $taskProject = explode( '|', $taskProject );
    $api_parameters[] =
        array(
            'type' => 'projects.set',
            'value' => $taskProject
        );
}

if( $taskSubscriber !== false ) {
    $taskSubscriber = explode( '|', $taskSubscriber );
    $api_parameters[] =
        array(
            'type' => 'subscribers.set',
            'value' => $taskSubscriber
        );
}

if( $taskId !== false ) {
    $identifier = $taskId;
} else $identifier = false;


if( $usrt !== false ) {
    if ($identifier !== false ) {
        $api_parameters[] =
            array(
                'type' => 'comment',
                'value' => "Tasker updated this task on behalve of " . $usrt
            );
    } else {
        $api_parameters[] =
            array(
                'type' => 'comment',
                'value' => "Tasker created this task on behalve of " . $usrt
            );
    }
}


$phab = new phabricator();
$result = $phab->apiPost($api_parameters,$identifier);
