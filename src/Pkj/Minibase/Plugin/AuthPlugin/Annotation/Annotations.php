<?php
namespace Pkj\Minibase\Plugin\AuthPlugin\Annotation;

use Doctrine\Common\Annotations\Annotation as DoctrineAnnotation;

abstract class AuthAnnotation {
	public $redirect = null; // Redirects to specific location

}

/**
 * User must be authenticated.
 * @Annotation
 */
class Authenticated extends AuthAnnotation{
	
}
/**
 * User must not be authenticated.
 * @Annotation
 */
class NotAuthenticated extends AuthAnnotation{
	
}
/**
 * User must be in one of the following groups.
 * @Annotation
 * @Attributes({
		@Attribute("groups", required=true, type="array")
   })
 */
class UserGroups {
	/**
	 * @var array
	 */
	public $groups;
}