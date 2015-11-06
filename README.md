Magme - Magento mass exporter
============================
Usage
============================
Clone repository into magme folder.
```
cd __MAGENTO_DIR__
git clone https://github.com/lisi4ok/magme.git magme
cd magme
```
============================
Run the Shell script
```
php shell.php
```
Or open browser and go to:
```
//__MAGENTO_URL__/magme
```
============================
Copy the products.csv
```
cp products.csv ../var/import
```
Copy the product images
```
cd ../media/catalog/product
cp -R * ../../import/*
```
============================
Note: Make sure you create all "Attributes" and "AttributeSets" and associate the "Attributes" to the "AttributeSets".
============================
Run magmi import.
