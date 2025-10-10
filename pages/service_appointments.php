<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Appointments - Mitsubishi Motors</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 25%, #2d1b1b 50%, #8b0000 75%, #b80000 100%);
            min-height: 100vh;
            color: white;
        }

        .header {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 50px;
            height: auto;
        }

        .brand-text {
            font-size: 1.2rem;
            font-weight: 700;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-btn {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 10px 20px;
            border-radius: 15px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-btn:hover {
            background: rgba(255, 215, 0, 0.2);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 3rem;
            background: linear-gradient(45deg, #ffd700, #ffed4e, #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .service-card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 215, 0, 0.1);
            transition: all 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        .service-icon {
            font-size: 3rem;
            color: #ffd700;
            margin-bottom: 20px;
        }

        .service-card h3 {
            color: #ffd700;
            font-size: 1.4rem;
            margin-bottom: 15px;
        }

        .service-card p {
            margin-bottom: 15px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .service-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ffed4e;
            margin-bottom: 20px;
        }

        .btn-book {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        .appointment-form {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 215, 0, 0.1);
            margin-top: 40px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #ffd700;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ffd700;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }

        @media (max-width: 575px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .container {
                padding: 30px 20px;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .services-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="../../includes/images/mitsubishi_logo.png" alt="Mitsubishi Logo" class="logo">
            <div class="brand-text">MITSUBISHI MOTORS</div>
        </div>
        <div class="nav-links">
            <a href="customer.php" class="nav-btn"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php" class="nav-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1>Service Appointments</h1>
            <p>Professional maintenance and repair services for your Mitsubishi</p>
        </div>

        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-oil-can"></i>
                </div>
                <h3>Oil Change Service</h3>
                <p>Regular oil changes to keep your engine running smoothly with genuine Mitsubishi oil and filters.</p>
                <div class="service-price">From $45</div>
                <button class="btn-book" onclick="selectService('Oil Change')">Book Now</button>
            </div>

            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <h3>General Maintenance</h3>
                <p>Comprehensive check-up including fluids, belts, hoses, and system diagnostics.</p>
                <div class="service-price">From $120</div>
                <button class="btn-book" onclick="selectService('General Maintenance')">Book Now</button>
            </div>

            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-tire"></i>
                </div>
                <h3>Tire Service</h3>
                <p>Tire rotation, balancing, alignment, and replacement with quality Mitsubishi-approved tires.</p>
                <div class="service-price">From $80</div>
                <button class="btn-book" onclick="selectService('Tire Service')">Book Now</button>
            </div>

            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-car-battery"></i>
                </div>
                <h3>Battery & Electrical</h3>
                <p>Battery testing, replacement, and electrical system diagnostics by certified technicians.</p>
                <div class="service-price">From $95</div>
                <button class="btn-book" onclick="selectService('Battery & Electrical')">Book Now</button>
            </div>

            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-car-crash"></i>
                </div>
                <h3>Brake Service</h3>
                <p>Complete brake inspection, pad replacement, and brake fluid service for optimal safety.</p>
                <div class="service-price">From $150</div>
                <button class="btn-book" onclick="selectService('Brake Service')">Book Now</button>
            </div>

            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-snowflake"></i>
                </div>
                <h3>A/C Service</h3>
                <p>Air conditioning system check, refrigerant refill, and climate control maintenance.</p>
                <div class="service-price">From $110</div>
                <button class="btn-book" onclick="selectService('A/C Service')">Book Now</button>
            </div>
        </div>

        <div class="appointment-form">
            <div class="form-header">
                <h2>Book Your Appointment</h2>
                <p>Schedule your service appointment at your convenience</p>
            </div>

            <form>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="serviceType">Service Type</label>
                        <select id="serviceType" name="serviceType" required>
                            <option value="">Select a service</option>
                            <option value="Oil Change">Oil Change Service</option>
                            <option value="General Maintenance">General Maintenance</option>
                            <option value="Tire Service">Tire Service</option>
                            <option value="Battery & Electrical">Battery & Electrical</option>
                            <option value="Brake Service">Brake Service</option>
                            <option value="A/C Service">A/C Service</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="preferredDate">Preferred Date</label>
                        <input type="date" id="preferredDate" name="preferredDate" required>
                    </div>

                    <div class="form-group">
                        <label for="preferredTime">Preferred Time</label>
                        <select id="preferredTime" name="preferredTime" required>
                            <option value="">Select time</option>
                            <option value="08:00">8:00 AM</option>
                            <option value="09:00">9:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="13:00">1:00 PM</option>
                            <option value="14:00">2:00 PM</option>
                            <option value="15:00">3:00 PM</option>
                            <option value="16:00">4:00 PM</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="vehicleYear">Vehicle Year</label>
                        <input type="number" id="vehicleYear" name="vehicleYear" min="1990" max="2024" placeholder="e.g., 2020">
                    </div>

                    <div class="form-group">
                        <label for="vehicleModel">Vehicle Model</label>
                        <input type="text" id="vehicleModel" name="vehicleModel" placeholder="e.g., Outlander">
                    </div>

                    <div class="form-group">
                        <label for="mileage">Current Mileage</label>
                        <input type="number" id="mileage" name="mileage" placeholder="e.g., 45000">
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Additional Notes</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Any specific concerns or requests..."></textarea>
                </div>

                <button type="submit" class="btn-book">
                    <i class="fas fa-calendar-check"></i> Schedule Appointment
                </button>
            </form>
        </div>
    </div>

    <script>
        function selectService(serviceName) {
            document.getElementById('serviceType').value = serviceName;
            document.getElementById('preferredDate').focus();
        }

        // Set minimum date to today
        document.getElementById('preferredDate').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>
