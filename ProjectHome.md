Use Case (Scenario):

User clicks Login/Register Here button

Registration form displayed with extra NxtLvlConfirm field

User fills out form including NxtLvlConfirm number

User submits form

Normal CB User email confirmation sent

User clicks email confirmation link

CB onAfterUserConfirm Event triggers new plugin to email "Accounting" to confirm NxtLvlConfirm, if entered

"Accounting" clicks confirmation link which triggers new plugin which upgrades User Access level

User able to view/download NxtLvlConfirm content