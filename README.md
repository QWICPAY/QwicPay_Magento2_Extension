
# QwicPay Magento 2 Checkout Integration

This guide provides step-by-step instructions for developers to install, configure, and activate the QwicPay Instant Checkout module for Magento 2.

---

## 🔧 Installation

1. **Login to your Magento Server**
2. **Navigate to your Magento root directory**

3. **Require the QwicPay module via Composer:**
   ```bash
   composer require qwicpay/qwicpay-magento2-checkout
   ```

4. **Enable the QwicPay module:**
   ```bash
   php bin/magento module:enable Qwicpay_Checkout
   ```

   You should see:
   ```
   The following modules have been enabled: Qwicpay_Checkout
   ```

5. **Upgrade the setup:**
   ```bash
   php bin/magento setup:upgrade
   ```

6. **Compile Magento (this may take several minutes):**
   ```bash
   php bin/magento setup:di:compile
   ```

---

## ⚙️ Configuration

1. Go to your **Magento Admin Panel**
2. Navigate to:  
   `Stores` → `Configuration` → `Sales` → `QwicPay`

3. Enter your **Merchant ID**,  
   Choose your preferred **Button Style**,  
   Then click **Save Config**

4. Magento may prompt you to **Flush Cache**.

> ✅ The QwicPay Checkout Button Block should now be available under `Content → Blocks`

---



## 🔄 Changing Button Style

When updating the QwicPay **Button Style** in:

`Stores → Configuration → Sales → QwicPay`

Magento may prompt you to **Flush Cache** after saving your changes. 
To proceed, click the **Flush Magento Cache** button to ensure your updates are applied.

---



## 🔐 Activate Integration

1. Navigate to:  
   `System` → `Integrations` → `Add New Integration`

2. Fill in the following under **Integration Info**:
   - **Name**:  
     `QwicPay`
   - **Email**:  
     [support@qwicpay.com](mailto:support@qwicpay.com)
   - **Callback URL**:  
     `https://ice.qwicpay.com/app/magento/auth/callback`
   - **Identity Link URL**:  
     `https://ice.qwicpay.com/app/magento/auth/link`

3. Under **API Permissions**, set:
   - **Resource Access**: `Custom`
     - `Sales`
     - `Catalog`
     - `Carts`
     - `Reports`
     - `Stores`

4. Click **Save**  
   You should now see the integration listed with a status of **Inactive**

5. Click **Activate** → then click **Allow**

6. A QwicPay popup will appear - enter the **Merchant ID** provided to you.

7. Click **Link to QwicPay**  
   Ensure the integration status updates to **Active**

---

## 🛒 Add QwicPay to Cart Page / Drawer

> ⚠️ As we do not have access to your theme or custom code, please ensure the following:

- Add the QwicPay button above your current checkout button (cart page and/or cart drawer).
- Implement logic to **hide the QwicPay button** if the cart is empty - the button will throw an error otherwise.

---

## 🚦 Final Steps

- The QwicPay button will launch in **Test Mode** by default.
- Once your integration is complete, please **contact us** to switch your button to **Production Mode**.

---

## 📊 Access QwicPay Merchant Portal from Admin

You can now access your **QwicPay Merchant Access Portal** directly from the Magento Admin Panel:

Navigate to: 
`Admin Page → Navbar → QwicPay → Dashboard`

This will take you directly to your merchant dashboard where you can your manage your QwicPay transactions.

---

For any issues or assistance, please reach out to: [support@qwicpay.com](mailto:support@qwicpay.com)
