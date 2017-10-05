# Lora-appserver
Application server for LoRaWAN test purposes, saving data sent by Actility TPW

## Purpose
This project contains a script for a small Python webserver that will log data sent by LoRaWAN devices.
The webserver is waiting for a post from Actility TPW and will extract the data in the JSON posted.
The extracted data is logged to a logfile (lora_appserver.log) and/or MySQL DB.
I've also provided a sample PHP file to display the data saved to the database.

## Getting started
Follow the steps below to get started using this script.

### Prerequisites
* Python 2.X installed
* MySQL/MariaDB installed and configured 
* Apache: installed and configured (optional)
* PHP (optional)

### Installation
Step 1) create a table for the lora log data in MySQL
You can use the create_table_loralog.sql or create the table manually
Make sure you also have or create a user with sufficient access on the database and table

Step 2) copy the lora_appserver.py script to your machine and tailor if needed
In the lora_appserver.py script, change the credentials to match your MySQL installation
I've also  included a few examples of payload conversion for popular testdevices.

Step 3) (optional) copy the index.php file to a location accessible by Apache

Step 4) (optional) configure Apache to listen on a different port than what you will be using to receive the data from Actility TPW

Step 5) (optional) customize index.php as desired. By default, a graph that shows the last 30 values is displayed for temperature and light-sensor data.

### Starting
Run lora_appserver.py and give the port to listen to as the first argument
```
$ python lora_appserver.py 8080
```
To run the appserver in the background:
```
$ nohup python lora_appserver.py 8080 &
```

## Results
The data received on the webserver is written to the logfile "lora_appserver.log" by default.
If all goes well, your data should also be written to the MySQL table you created.

To access the data using the PHP example, navigate to the location on the webserver as specified in installation step 3) using your browser

### Example
![alt tag](http://jensd.be/lora-appserver.png)
