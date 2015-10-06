# NYC Restaurant
## Collects data from nycopendata and uses it to find the top 10 restaurants filtered by food type

Logic: this script would act as a cronjob (per day) to download the csv file and process its data
1. Download the csv
2. Perform prerequisite checks on the file downloaded to make sure its safe to process
3. Open file and process line by line, delimitting line by comma
4. Make sure that the data we're inserting is not duplicate. This ensures that the script will run faster each time its run
5. Compute the top 10 food variance based on foodtype

Structure wise, I decided to create a staging layer that would contain all the noisy data waiting to be processed
All the transforming would be processed inside staging, and then moved to prod for usage. This would provide faster access.
Both databases would implement indexes for all important keys such as camis

I also made it a choice to pull the data for all food types - this way, if my friend's taste changes from Thai food to Hamburgers, the application would be able to support his lifestyle

Finally, I thought it might be important to store the historical inspection data - with it, I can provide statistics on how the restaurant performed over time.

First Run: 6.8hrs to finish loading all 500,000 into the database (keep in mind, this aggregated historical, non duplicate data)
Second Run: 1.2hrs to run through all data, validating existing
Third Run (after indexing was done): 0.4hrs

---

Author: Zill Christian
Credit: socrata nycopendata