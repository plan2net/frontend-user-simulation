# TYPO3 Frontend User Simulation Extension

## Frontend User Simulation via TYPO3 Backend
This extension allows backend users to simulate frontend user accounts directly from the TYPO3 backend. This functionality is similar to the deprecated cabag_loginas extension but updated for TYPO3 v12. It's particularly useful for administrators who need to troubleshoot user-specific issues in the frontend by temporarily assuming the role of a frontend user. Only frontend users that are active can be simulated.

## Requirements
* TYPO3 12 LTS

## Installation
Require via composer:
```
  composer require "plan2net/frontend-user-simulation"
```

or add via composer.json:
```
"require": {
  "plan2net/frontend-user-simulation": "*"
}
```

## Usage
To simulate a frontend user:

Navigate to the storage folder where the frontend users are located in the TYPO3 backend.

Select the frontend user you want to simulate by clicking on the user icon:

![frontend_user_simulation](https://github.com/plan2net/frontend-user-simulation/assets/137509922/0f480f75-d68e-40c0-b313-1d59bbb2f507)

and you will be redirected in the frontend, logged in as the chosen user.
