import time
import board
import adafruit_dht
import spidev
import lgpio
import mysql.connector
from datetime import datetime
from numpy import interp

# DHT22 Sensor configuration
dht_device = adafruit_dht.DHT22(board.D17)  # BCM pin 17 (physical pin 11)

# Soil moisture sensor configuration
SPI_CHANNEL = 0
SPI_DEVICE = 0

# Pump configuration
PUMP_PIN = 27  # GPIO pin connected to the relay module controlling the pump

# Initialize GPIO
h = lgpio.gpiochip_open(0)
lgpio.gpio_claim_output(h, PUMP_PIN)

# Initialize SPI
spi = spidev.SpiDev()
spi.open(SPI_CHANNEL, SPI_DEVICE)
spi.max_speed_hz = 1350000

# Database configuration
db_config = {
    'user': 'root',
    'password': 'ubuntu',
    'host': 'localhost',
    'database': 'history'
}

def read_dht22():
    try:
        temperature = dht_device.temperature
        humidity = dht_device.humidity
        return temperature, humidity
    except RuntimeError as error:
        print(f"Error reading DHT22: {error.args[0]}")
        return None, None

def read_soil_moisture(channel=0):
    adc = spi.xfer2([1, (8 + channel) << 4, 0])
    data = ((adc[1] & 3) << 8) + adc[2]
    moisture = interp(data, [0, 1023], [100, 0])  # Scale the data
    return int(moisture)

def log_to_database(temp, hum, soil_hum):
    if temp is not None and hum is not None:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        cursor.execute("INSERT INTO dataload (air_temp, air_hum, soil_hum) VALUES (%s, %s, %s)", (temp, hum, soil_hum))
        conn.commit()
        cursor.close()
        conn.close()
    else:
        print("Failed to log data: Temperature or Humidity is None")

def log_pump_activity():
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    cursor.execute("INSERT INTO pumpload (timestamp) VALUES (%s)", (timestamp,))
    conn.commit()
    cursor.close()
    conn.close()

def check_manual_pump():
    conn = mysql.connector.connect(user='root', password='ubuntu', host='localhost', database='hardware')
    cursor = conn.cursor()
    cursor.execute("SELECT pump FROM run")
    result = cursor.fetchone()
    if result and result[0] == 1:
        activate_pump()
        cursor.execute("UPDATE run SET pump = 0")
        conn.commit()
    cursor.close()
    conn.close()

def activate_pump():
    lgpio.gpio_write(h, PUMP_PIN, 1)
    time.sleep(10)
    lgpio.gpio_write(h, PUMP_PIN, 0)
    log_pump_activity()

def main():
    while True:
        current_time = datetime.now()
        
        # Read sensor data
        air_temp, air_hum = read_dht22()
        soil_hum = read_soil_moisture()
        
        print(f"Air Temp: {air_temp}, Air Hum: {air_hum}, Soil Hum: {soil_hum}")

        # Log sensor data to database if valid
        if air_temp is not None and air_hum is not None:
            log_to_database(air_temp, air_hum, soil_hum)
        
            # Check if pump needs to be activated based on soil humidity and time
            if current_time.hour in [8, 12, 16] and soil_hum < 30:  # Soil humidity < 30%
                activate_pump()
        
        # Check for manual pump activation
        check_manual_pump()
        
        # Wait for 5 seconds before next reading
        time.sleep(5)

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        lgpio.gpiochip_close(h)
        spi.close()
        dht_device.exit()
        print("Program terminated.")

