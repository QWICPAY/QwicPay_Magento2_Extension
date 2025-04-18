<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Config:etc/system_file.xsd">
    <system>
        <section id="qwicpay" translate="label" type="text" sortOrder="900" showInDefault="1" showInWebsite="1" showInStore="1">
            <label>QwicPay</label>
            <tab>sales</tab>
            <resource>Qwicpay_Checkout::config</resource>
            <group id="general" translate="label" type="text" sortOrder="10" showInDefault="1" showInWebsite="1" showInStore="1">
                <label>General</label>
                <field id="merchant_id" translate="label" type="text" sortOrder="10" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Merchant ID</label>
                </field>
                <field id="stage" translate="label" type="select" sortOrder="20" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Stage</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                    <option id="TEST">Test</option>
                    <option id="PROD">Production</option>
                </field>
            </group>
        </section>
    </system>
</config>