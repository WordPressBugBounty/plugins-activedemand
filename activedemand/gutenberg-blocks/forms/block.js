const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks;

registerBlockType( 'activedemand/form', {
	title: __( 'ActiveDEMAND - Web Form' ),
	icon: 'forms',
	category: 'activedemand-blocks',
	keywords: [
		__( 'Web Forms' ),
		__( 'Form' )
	],
	attributes: {
		form_id: {
			type: 'number'
		}
	},
	edit: function( props ) {
		const selectStyle = {fontSize: '14px', paddingRight: '5px'};
		const { attributes: { form_id }, setAttributes } = props;

        function setFormId( event ) {
            const selected = event.target.querySelector( 'option:checked' );
            props.setAttributes( { form_id: Number(selected.value) } );
            event.preventDefault();
        }
 
        return (
            <div className={ props.className }>
            	<label style={ selectStyle } >ActiveDEMAND Form</label>
				<select value={ form_id } onChange={ setFormId }>
					{activedemand_forms.map(option => (
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
