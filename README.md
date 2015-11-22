# NYC Restaurants

Collects data from nycopendata and uses it to find the top 10 restaurants filtered by food type. For those days when seamless fails me.

###Logic

This script would act as a cronjob (per day) to download the csv file and process its data

- Download the csv
- Perform prerequisite checks on the file downloaded to make sure its safe to process
- Open file and process line by line, delimitting line by comma
- Make sure that the data we're inserting is not duplicate. This ensures that the script will run faster each time its run
- Compute the top 10 food variance based on foodtype

###Benchmarks

First Run
> 6.8hrs to finish loading all 500,000 into the database (keep in mind, this aggregated historical, non duplicate data)

Second Run
> 1.2hrs to run through all data, validating existing

Third Run (after indexing was done):
> 0.4hrs

---

Zill Christian

Credit: socrata nycopendata