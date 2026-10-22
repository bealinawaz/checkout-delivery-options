/**
 * Delivery options repeater: add, remove, and reorder rows.
 */
( function () {
	'use strict';

	var tbody = document.getElementById( 'cdopt-rows' );
	var template = document.getElementById( 'cdopt-row-template' );
	var addButton = document.getElementById( 'cdopt-add' );
	if ( ! tbody || ! template || ! addButton ) {
		return;
	}

	var __ = window.wp && window.wp.i18n ? window.wp.i18n.__ : function ( text ) {
		return text;
	};
	var counter = Date.now();
	var dragged = null;

	function announce( message ) {
		if ( window.wp && window.wp.a11y && window.wp.a11y.speak ) {
			window.wp.a11y.speak( message );
		}
	}

	function addRow() {
		var html = template.innerHTML.replace( /__INDEX__/g, String( counter++ ) );
		var holder = document.createElement( 'tbody' );
		holder.innerHTML = html;
		var row = holder.querySelector( 'tr' );
		tbody.appendChild( row );
		row.querySelector( '.cdopt-row__label' ).focus();
		announce( __( 'Option added.', 'checkout-delivery-options' ) );
	}

	function removeRow( row ) {
		var next = row.nextElementSibling || row.previousElementSibling;
		row.parentNode.removeChild( row );
		if ( next ) {
			next.querySelector( '.cdopt-remove' ).focus();
		} else {
			addButton.focus();
		}
		announce( __( 'Option removed. Save changes to apply.', 'checkout-delivery-options' ) );
	}

	function moveRow( row, direction, button ) {
		if ( 'up' === direction && row.previousElementSibling ) {
			tbody.insertBefore( row, row.previousElementSibling );
		} else if ( 'down' === direction && row.nextElementSibling ) {
			tbody.insertBefore( row.nextElementSibling, row );
		} else {
			return;
		}
		button.focus();
		announce( __( 'Option moved.', 'checkout-delivery-options' ) );
	}

	addButton.addEventListener( 'click', addRow );

	tbody.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( 'button' );
		if ( ! button ) {
			return;
		}
		var row = button.closest( 'tr' );
		if ( button.classList.contains( 'cdopt-remove' ) ) {
			removeRow( row );
		} else if ( button.classList.contains( 'cdopt-move' ) ) {
			moveRow( row, button.getAttribute( 'data-direction' ), button );
		}
	} );

	// Drag and drop, started from the handle only so text inputs stay selectable.
	tbody.addEventListener( 'mousedown', function ( event ) {
		var row = event.target.closest( 'tr' );
		if ( row ) {
			row.setAttribute( 'draggable', event.target.closest( '.cdopt-row__handle' ) ? 'true' : 'false' );
		}
	} );

	tbody.addEventListener( 'dragstart', function ( event ) {
		dragged = event.target.closest( 'tr' );
		if ( ! dragged ) {
			return;
		}
		dragged.classList.add( 'is-dragging' );
		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.setData( 'text/plain', '' );
	} );

	tbody.addEventListener( 'dragover', function ( event ) {
		var target = event.target.closest( 'tr' );
		if ( ! dragged || ! target || target === dragged ) {
			return;
		}
		event.preventDefault();
		var rect = target.getBoundingClientRect();
		var after = event.clientY > rect.top + rect.height / 2;
		tbody.insertBefore( dragged, after ? target.nextElementSibling : target );
	} );

	tbody.addEventListener( 'dragend', function () {
		if ( dragged ) {
			dragged.classList.remove( 'is-dragging' );
			dragged = null;
		}
	} );
} )();
