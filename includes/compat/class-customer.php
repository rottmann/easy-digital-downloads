<?php
/**
 * Backwards Compatibility Handler for Customers.
 *
 * @package     EDD
 * @subpackage  Compat
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0.0
 */
namespace EDD\Compat;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Customer Class.
 *
 * @since 3.0.0
 */
class Customer extends Base {

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->set_component( 'customer' );
		$this->hooks();
	}

	/**
	 * Magic method to handle calls to method that no longer exist.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name      Name of the method.
	 * @param array  $arguments Enumerated array containing the parameters passed to the $name'ed method.
	 * @return mixed Dependent on the method being dispatched to.
	 */
	public function __call( $name, $arguments ) {
		switch ( $name ) {
			case 'get_column':
				return edd_get_customer_by( $arguments[0], $arguments[1] );
				break;
			case 'attach_payment':
				/** @var $customer \EDD_Customer */
				$customer = edd_get_customer( $arguments[0] );
				return $customer->attach_payment( $arguments[1], false );
				break;
			case 'remove_payment':
				/** @var $customer \EDD_Customer */
				$customer = edd_get_customer( $arguments[0] );
				return $customer->remove_payment( $arguments[1], false );
				break;
			case 'increment_stats':
				/** @var $customer \EDD_Customer */
				$customer = edd_get_customer( $arguments[0] );

				$increased_count = $customer->increase_purchase_count();
				$increased_value = $customer->increase_value( $arguments[1] );

				return ( $increased_count && $increased_value ) ? true : false;
				break;
			case 'decrement_stats':
				/** @var $customer \EDD_Customer */
				$customer = edd_get_customer( $arguments[0] );

				$decreased_count = $customer->decrease_purchase_count();
				$decreased_value = $customer->decrease_value( $arguments[1] );

				return ( $decreased_count && $decreased_value ) ? true : false;
				break;
		}
	}

	/**
	 * Backwards compatibility hooks for customers.
	 *
	 * @since 3.0.0
	 * @access private
	 */
	private function hooks() {
		add_action( 'profile_update', array( $this, 'update_customer_email_on_user_update' ), 10, 2 );
	}

	/**
	 * Updates the email address of a customer record when the email on a user is updated.
	 *
	 * @since 2.4.0
	 *
	 * @param int   $user_id       User ID.
	 * @param array $old_user_data Old user data.
	 * @return bool False if customer does not exist for given user ID.
	 */
	public function update_customer_email_on_user_update( $user_id = 0, $old_user_data ) {
		$customer = edd_get_customer_by( 'user_id', $user_id );

		if ( ! $customer ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! empty( $user ) && $user->user_email !== $customer->email ) {
			if ( ! edd_get_customer_by( 'email', $user->user_email ) ) {
				$success = edd_update_customer( $customer->id, array(
					'email' => $user->user_email
				) );

				if ( $success ) {
					$payments_array = explode( ',', $customer->payment_ids );
					if ( ! empty( $payments_array ) ) {
						foreach ( $payments_array as $payment_id ) {
							edd_update_payment_meta( $payment_id, 'email', $user->user_email );
						}
					}

					do_action( 'edd_update_customer_email_on_user_update', $user, $customer );
				}
			}
		}
	}
}