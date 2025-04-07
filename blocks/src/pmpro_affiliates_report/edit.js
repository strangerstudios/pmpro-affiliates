import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { ToggleControl, PanelBody } from '@wordpress/components';

function Edit({ attributes, setAttributes }) {
    const { back_link, export_csv, help, show_commissions_table, show_conversion_table, code, subid, name, user_login, date, membership_level, show_commission, total } = attributes
    const blockProps = useBlockProps({ className: "pmpro-block-element" });

    return ([
        <InspectorControls key={'controls'}>
            <PanelBody key={'settings'} title={__('Settings', 'pmpro-affiliates')}>
                <ToggleControl
                    key={'back_link'}
                    label={__('Back Link', 'pmpro-affiliates')}
                    checked={back_link}
                    onChange={back_link => { setAttributes({ back_link }) }}
                    help={__('Show a back link?', 'pmpro-affiliates')}
                />
                <ToggleControl
                    key={'export_csv'}
                    label={__('Export CSV', 'pmpro-affiliates')}
                    checked={export_csv}
                    onChange={export_csv => { setAttributes({ export_csv }) }}
                    help={__('Show Export CSV link', 'pmpro-affiliates')}
                />
                <ToggleControl
                    key={'help'}
                    label={__('Help', 'pmpro-affiliates')}
                    checked={help}
                    onChange={help => { setAttributes({ help }) }}
                    help={__('Show a help table', 'pmpro-affiliates')}
                />
                <ToggleControl
                    key={'show_conversion_table'}
                    label={__('Show Conversion Table', 'pmpro-affiliates')}
                    checked={show_conversion_table}
                    onChange={show_conversion_table => { setAttributes({ show_conversion_table }) }}
                    help={__('Show the conversion table', 'pmpro-affiliates')}
                />
                <ToggleControl
                    key={'show_commissions_table'}
                    label={__('Show Commissions Table', 'pmpro-affiliates')}
                    checked={show_commissions_table}
                    onChange={show_commissions_table => { setAttributes({ show_commissions_table }) }}
                    help={__('Show the commission table', 'pmpro-affiliates')}
                />
            </PanelBody>
            <PanelBody key={'table-fields'} title={__('Table Fields', 'pmpro-affiliates')}>
                <ToggleControl
                    key={'code'}
                    label={__('Code', 'pmpro-affiliates')}
                    checked={code}
                    onChange={code => { setAttributes({ code }) }}
                />
                <ToggleControl
                    key={'sub_id'}
                    label={__('Sub-ID', 'pmpro-affiliates')}
                    checked={subid}
                    onChange={subid => { setAttributes({ subid }) }}
                />
                <ToggleControl
                    key={'name'}
                    label={__('Name', 'pmpro-affiliates')}
                    checked={name}
                    onChange={name => { setAttributes({ name }) }}
                />
                <ToggleControl
                    key={'user_login'}
                    label={__('User Login', 'pmpro-affiliates')}
                    checked={user_login}
                    onChange={user_login => { setAttributes({ user_login }) }}
                />
                <ToggleControl
                    key={'date'}
                    label={__('Date', 'pmpro-affiliates')}
                    checked={date}
                    onChange={date => { setAttributes({ date }) }}
                />
                <ToggleControl
                    key={'membership_level'}
                    label={__('Membership Level', 'pmpro-affiliates')}
                    checked={membership_level}
                    onChange={membership_level => { setAttributes({ membership_level }) }}
                />
                <ToggleControl
                    key={'commision'}
                    label={__('Commission', 'pmpro-affiliates')}
                    checked={show_commission}
                    onChange={show_commission => { setAttributes({ show_commission }) }}
                />
                <ToggleControl
                    key={'total'}
                    label={__('Total', 'pmpro-affiliates')}
                    checked={total}
                    onChange={total => { setAttributes({ total }) }}
                />
            </PanelBody>
        </InspectorControls>,
        <div key={'main'} {...blockProps}>
            <span key={'title'} className="pmpro-block-title">{__('Paid Memberships Pro Affiliates', 'pmpro-affiliates')}</span>
            <span key={'subtitle'} className="pmpro-block-subtitle">{__('Report', 'pmpro-affiliates')}</span>
        </div>
    ]);
}

export default Edit;