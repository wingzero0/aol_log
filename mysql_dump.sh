#/bin/bash/

q=$1
t=$2
lbound=$3

for smooth_value in 0.0 0.5 1.0 2.0
do
	php mysql_dump.php -TB smooth_$smooth_value.set.1.train -query "$q" -t $t -low $lbound > "$q.$smooth_value.$lbound.csv"
done
