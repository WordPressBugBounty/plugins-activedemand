const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks;

registerBlockType( 'activedemand/storyboard', {
	title: __( 'ActiveDEMAND - Story board' ),
	icon: 'forms',
	category: 'activedemand-blocks',
	keywords: [
		__( 'Storyboards' ),
		__( 'Storyboard' )
	],
	attributes: {
		storyboard_id: {
			type: 'number'
		}
	},
	edit: function( props ) {
		const selectStyle = {fontSize: '14px', paddingRight: '5px'};
		const { attributes: { storyboard_id }, setAttributes } = props;

        function setstoryboardId( event ) {
            const selected = event.target.querySelector( 'option:checked' );
            props.setAttributes( { storyboard_id: Number(selected.value) } );
            event.preventDefault();
        }
 
        return (
            <div className={ props.className }>
            	<label style={ selectStyle } >ActiveDEMAND Storyboard</label>
				<select value={ storyboard_id } onChange={ setstoryboardId }>
					{activedemand_storyboard.map(option => (
                		<option value={option.value}>{option.label}</option>
                	))}
				</select>
            </div>
        );
	},
	save: function() {
		return null;
	}
} );
