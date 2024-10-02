const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks;

registerBlockType( 'activedemand/content-block', {	
	title: __( 'ActiveDEMAND - Dynamic Content Block' ),
	icon: 'editor-table',
	category: 'activedemand-blocks',
	keywords: [
		__( 'Dynamic Content Blocks' ),
		__( 'Dynamic Block' ),
		__( 'Block' ),
	],
	attributes: {
		block_id: {
			type: 'number'
		}
	},
	edit: function( props ) {
		const selectStyle = {fontSize: '14px', paddingRight: '5px'};
		const { attributes: { block_id }, setAttributes } = props;

        function setBlockId( event ) {
            const selected = event.target.querySelector( 'option:checked' );
            props.setAttributes( { block_id: Number(selected.value) } );
            event.preventDefault();
        }
 
        return (
            <div className={ props.className }>
            	<label style={ selectStyle } >ActiveDEMAND Block</label>
				<select value={ block_id } onChange={ setBlockId }>
					{activedemand_blocks.map(option => (
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
