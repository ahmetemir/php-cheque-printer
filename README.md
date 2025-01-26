Cheques
======
## Background
Welcome to Canada's ancient banking system.

## Warning

>Only produce cheques for your own accounts!

### Forgery (Section 366 of the Criminal Code)
#### Making or altering a cheque with intent to defraud.
- Indictable offense: Up to 10 years in prison
- Summary conviction: Lesser penalties such as fines or up to 2 years less a day in jail.

### Uttering a Forged Document (Section 368 of the Criminal Code)
#### Knowingly using a fraudulent cheque.  
- Indictable offense: Up to 10 years in prison
- Summary conviction: Lesser penalties.

### Fraud (Section 380 of the Criminal Code)
#### If the fraud amount exceeds $5,000, it can result in:
- Up to 14 years in prison
- Restitution orders (repayment of defrauded funds)
- A criminal record that can affect future employment and travel.

## Design

Spec:  
https://www.payments.ca/sites/default/files/standard006eng.pdf

![alt text](image-2.png)

## Installing
### Ubuntu
```bash
sudo apt install php-cli
```

## Running
```bash
php -S 127.0.0.1:8000
```

Cheque Paper
-----------

Make sure the paper you buy has the Canadian endorsement pre-printed text and not the US ones.
![alt text](image-1.png)

Depositing
----------

If you have a laser printer and can buy magnetic ink, the checks will work pretty much everywhere.

If you print this out with a regular inkjet or laser printer, I've heard of some ATMs that don't accept them, but otherwise work as normal. 

These days most checks are scanned via optical methods (camera phone on mobile bank deposit, or inside ATMs), so you shouldn't have a problem with non-magnetic ink. Legally, a check doesn't have to have magnetic ink, it was just historically the best way for machines to scan the routing and account number.


