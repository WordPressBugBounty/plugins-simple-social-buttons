<?php
/**
 * Inline custom CSS for icon spacing.
 *
 * WPCS debt: legacy CSS template with inline echo blocks; full refactor deferred.
 *
 * @package SimpleSocialButtons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! isset( $this->selected_position['inline'] ) && ! isset( $this->selected_position['sidebar'] ) ) {
	return;
}

$ssb_inline_icon_margin  = ssb_icon_space_margin( $this->inline_option ?? array(), 'px' );
$ssb_sidebar_icon_margin = ssb_icon_space_margin( $this->sidebar_option ?? array(), 'px 0' );

if ( '' === $ssb_inline_icon_margin && '' === $ssb_sidebar_icon_margin ) {
	return;
}
?>
<?php // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Legacy CSS template; refactor deferred. ?>
<style media="screen">

	<?php
	if ( isset( $this->selected_position['inline'] ) && '' !== $ssb_inline_icon_margin ) :
		?>
	.simplesocialbuttons.simplesocialbuttons_inline .ssb-fb-like,
	.simplesocialbuttons.simplesocialbuttons_inline amp-facebook-like {
		margin: <?php echo $ssb_inline_icon_margin; ?>;
	}
	<?php endif ?>
	/*inline margin*/
	<?php
	if ( 'sm-round' === $this->selected_theme && isset( $this->selected_position['inline'] ) && '' !== $ssb_inline_icon_margin ) :
		?>
	.simplesocialbuttons.simplesocialbuttons_inline.simplesocial-sm-round button{
		margin: <?php echo $ssb_inline_icon_margin; ?>;
	}
	<?php endif ?>

	<?php
	if ( 'simple-round' === $this->selected_theme && isset( $this->selected_position['inline'] ) && '' !== $ssb_inline_icon_margin ) :
		?>
	.simplesocialbuttons.simplesocialbuttons_inline.simplesocial-simple-round button{
		margin: <?php echo $ssb_inline_icon_margin; ?>;
	}
	<?php endif ?>

	<?php
	if ( 'round-txt' === $this->selected_theme && isset( $this->selected_position['inline'] ) && '' !== $ssb_inline_icon_margin ) :
		?>
	.simplesocialbuttons.simplesocialbuttons_inline.simplesocial-round-txt button{
		margin: <?php echo $ssb_inline_icon_margin; ?>;
	}
	<?php endif ?>

	<?php
	if ( 'round-btm-border' === $this->selected_theme && isset( $this->selected_position['inline'] ) && '' !== $ssb_inline_icon_margin ) :
		?>
	.simplesocialbuttons.simplesocialbuttons_inline.simplesocial-round-btm-border button{
		margin: <?php echo $ssb_inline_icon_margin; ?>;
	}
	<?php endif ?>

	<?php
	if ( 'flat-button-border' === $this->selected_theme && isset( $this->selected_position['inline'] ) && '' !== $ssb_inline_icon_margin ) :
		?>
	.simplesocialbuttons.simplesocialbuttons_inline.simplesocial-flat-button-border button{
		margin: <?php echo $ssb_inline_icon_margin; ?>;
	}
	<?php endif ?>

	<?php
	if ( 'round-icon' === $this->selected_theme && isset( $this->selected_position['inline'] ) && '' !== $ssb_inline_icon_margin ) :
		?>
	.simplesocialbuttons.simplesocialbuttons_inline.simplesocial-round-icon button{
		margin: <?php echo $ssb_inline_icon_margin; ?>;
	}

	<?php endif ?>

		<?php
		if ( 'simple-icons' === $this->selected_theme && isset( $this->selected_position['inline'] ) && '' !== $ssb_inline_icon_margin ) :
			?>
	.simplesocialbuttons.simplesocialbuttons_inline.simplesocial-simple-icons button{
		margin: <?php echo $ssb_inline_icon_margin; ?>;
	}

		<?php endif ?>
	/*margin-digbar*/

	<?php
	if ( 'sm-round' === $this->selected_theme && isset( $this->selected_position['sidebar'] ) && '' !== $ssb_sidebar_icon_margin ) :
		?>
	div[class*="simplesocialbuttons-float"].simplesocialbuttons.simplesocial-sm-round button{
		margin: <?php echo $ssb_sidebar_icon_margin; ?>;
	}
	<?php endif ?>

	<?php
	if ( 'simple-round' === $this->selected_theme && isset( $this->selected_position['sidebar'] ) && '' !== $ssb_sidebar_icon_margin ) :
		?>
	div[class*="simplesocialbuttons-float"].simplesocialbuttons.simplesocial-simple-round button{
		margin: <?php echo $ssb_sidebar_icon_margin; ?>;
	}
	<?php endif ?>

	<?php
	if ( 'round-txt' === $this->selected_theme && isset( $this->selected_position['sidebar'] ) && '' !== $ssb_sidebar_icon_margin ) :
		?>
	div[class*="simplesocialbuttons-float"].simplesocialbuttons.simplesocial-round-txt button{
	margin: <?php echo $ssb_sidebar_icon_margin; ?>;
	}
	<?php endif ?>

	<?php
	if ( 'round-btm-border' === $this->selected_theme && isset( $this->selected_position['sidebar'] ) && '' !== $ssb_sidebar_icon_margin ) :
		?>
	div[class*="simplesocialbuttons-float"].simplesocialbuttons.simplesocial-round-btm-border button{
		margin: <?php echo $ssb_sidebar_icon_margin; ?>;
	}
	<?php endif ?>

	<?php
	if ( 'round-icon' === $this->selected_theme && isset( $this->selected_position['sidebar'] ) && '' !== $ssb_sidebar_icon_margin ) :
		?>
	div[class*="simplesocialbuttons-float"].simplesocialbuttons.simplesocial-round-icon button{
	margin: <?php echo $ssb_sidebar_icon_margin; ?>;
	}
	<?php endif ?>

	<?php
	if ( 'simple-icons' === $this->selected_theme && isset( $this->selected_position['sidebar'] ) && '' !== $ssb_sidebar_icon_margin ) :
		?>
	div[class*="simplesocialbuttons-float"].simplesocialbuttons.simplesocial-simple-icons button{
		margin: <?php echo $ssb_sidebar_icon_margin; ?>;
	}
	div[class*="simplesocialbuttons-float"].simplesocialbuttons.simplesocial-simple-icons .ssb-fb-like,
	div[class*="simplesocialbuttons-float"].simplesocialbuttons.simplesocial-simple-icons amp-facebook-like{
		margin: <?php echo $ssb_sidebar_icon_margin; ?>;
	}
	<?php endif ?>

	<?php
	if ( isset( $this->selected_position['sidebar'] ) && '' !== $ssb_sidebar_icon_margin ) :
		?>
	div[class*="simplesocialbuttons-float"].simplesocialbuttons .ssb-fb-like,
	div[class*="simplesocialbuttons-float"].simplesocialbuttons amp-facebook-like{
		margin: <?php echo $ssb_sidebar_icon_margin; ?>;
	}
	<?php endif ?>

</style>
<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
