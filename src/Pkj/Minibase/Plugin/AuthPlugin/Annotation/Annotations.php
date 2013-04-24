<?php
namespace Pkj\Minibase\Plugin\AuthPlugin\Annotation;

abstract class AuthAnnotation {
	public $redirect = null; // Redirects to specific location
	public $saveCall = false; // Save the call and after redirect execute the call.
	public $flash = null;
}

/**
 * User must be authenticated.
 * @Annotation
 */
class Authenticated extends AuthAnnotation{
	public $redirectLogin = false; // redirects to login if not authenticated
	
}
/**
 * User must not be authenticated.
 * @Annotation
 */
class NotAuthenticated extends AuthAnnotation{
	
}