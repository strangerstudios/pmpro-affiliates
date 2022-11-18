import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { ToggleControl, PanelBody } from '@wordpress/components';

function Edit({ attributes, setAttributes }) {
    const { back_link, export_csv, help, code, subid, name, user_login, date, membership_level, show_commission, total } = attributes
    const blockProps = useBlockProps({ className: "pmpro-block-element" });

    return ([
        <InspectorControls>
            <PanelBody title={ __( 'Settings', 'pmpro-affiliates' ) }>
                <ToggleControl
                    label={__('Back Link', 'pmpro-affiliates')}
                    checked={back_link}
                    onChange={back_link => { setAttributes({ back_link }) }}
                    help={__('Show a back link?', 'pmpro-affiliates')}
                />
                <ToggleControl
                    label={__('Export CSV', 'pmpro-affiliates')}
                    checked={export_csv}
                    onChange={export_csv => { setAttributes({ export_csv }) }}
                    help={__('Show Export CSV link', 'pmpro-affiliates')}
                />
                <ToggleControl
                    label={__('Help', 'pmpro-affiliates')}
                    checked={help}
                    onChange={help => { setAttributes({ help }) }}
                    help={__('Show a help table', 'pmpro-affiliates')}
                />
            </PanelBody>
            <PanelBody title={__('Table Fields', 'pmpro-affiliates')}>
                <ToggleControl
                    label={__('Code', 'pmpro-affiliates')}
                    checked={code}
                    onChange={code => { setAttributes({ code }) }}
                />
                <ToggleControl
                    label={__('Sub-ID', 'pmpro-affiliates')}
                    checked={subid}
                    onChange={subid => { setAttributes({ subid }) }}
                />
                <ToggleControl
                    label={__('Name', 'pmpro-affiliates')}
                    checked={name}
                    onChange={name => { setAttributes({ name }) }}
                />
                <ToggleControl
                    label={__('User Login', 'pmpro-affiliates')}
                    checked={user_login}
                    onChange={user_login => { setAttributes({ user_login }) }}
                />
                <ToggleControl
                    label={__('Date', 'pmpro-affiliates')}
                    checked={date}
                    onChange={date => { setAttributes({ date }) }}
                />
                <ToggleControl
                    label={__('Membership Level', 'pmpro-affiliates')}
                    checked={membership_level}
                    onChange={membership_level => { setAttributes({ membership_level }) }}
                />
                <ToggleControl
                    label={__('Commission', 'pmpro-affiliates')}
                    checked={show_commission}
                    onChange={show_commission => { setAttributes({ show_commission }) }}
                />
                <ToggleControl
                    label={__('Total', 'pmpro-affiliates')}
                    checked={total}
                    onChange={total => { setAttributes({ total }) }}
                />
            </PanelBody>
        </InspectorControls>,
        <div {...blockProps}>
            <span className="pmpro-block-title">{__('Paid Memberships Pro Affiliates', 'pmpro-affiliates')}</span>
            <span className="pmpro-block-subtitle">{__('Report', 'pmpro-affiliates')}</span>
        </div>
    ]);
}

export default Edit;