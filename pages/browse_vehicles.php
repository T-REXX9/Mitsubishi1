<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}

$displayName = !empty($_SESSION['username']) ? $_SESSION['username'] : 'Customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Vehicles - Mitsubishi Motors</title>
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
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
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
            max-width: 1400px;
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
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }

        .vehicles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .vehicle-card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 215, 0, 0.1);
        }

        .vehicle-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .vehicle-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(45deg, #333, #555);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #ffd700;
        }

        .vehicle-info {
            padding: 25px;
        }

        .vehicle-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffd700;
            margin-bottom: 10px;
        }

        .vehicle-price {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .vehicle-features {
            list-style: none;
            margin-bottom: 20px;
        }

        .vehicle-features li {
            padding: 5px 0;
            opacity: 0.9;
        }

        .vehicle-btn {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
        }

        .vehicle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        /* Media Queries */
        @media (max-width: 575px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .nav-links {
                flex-direction: column;
                width: 100%;
            }

            .container {
                padding: 30px 20px;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .vehicles-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (min-width: 576px) and (max-width: 767px) {
            .vehicles-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .vehicles-grid {
                grid-template-columns: repeat(2, 1fr);
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
            <h1>Browse Our Vehicles</h1>
            <p>Discover the perfect Mitsubishi vehicle for your lifestyle</p>
        </div>

        <div class="vehicles-grid">
            <div class="vehicle-card">
                <div class="vehicle-image">
                    <i class="fas fa-car"></i>
                </div>
                <div class="vehicle-info">
                    <div class="vehicle-name">Outlander</div>
                    <div class="vehicle-price">Starting at $35,000</div>
                    <ul class="vehicle-features">
                        <li><i class="fas fa-check"></i> 7-Seater SUV</li>
                        <li><i class="fas fa-check"></i> AWD Standard</li>
                        <li><i class="fas fa-check"></i> Advanced Safety Features</li>
                        <li><i class="fas fa-check"></i> 10-Year Warranty</li>
                    </ul>
                    <button class="vehicle-btn">View Details</button>
                </div>
            </div>

            <div class="vehicle-card">
                <div class="vehicle-image">
                    <i class="fas fa-car"></i>
                </div>
                <div class="vehicle-info">
                    <div class="vehicle-name">Eclipse Cross</div>
                    <div class="vehicle-price">Starting at $28,000</div>
                    <ul class="vehicle-features">
                        <li><i class="fas fa-check"></i> Compact SUV</li>
                        <li><i class="fas fa-check"></i> Turbocharged Engine</li>
                        <li><i class="fas fa-check"></i> Advanced Infotainment</li>
                        <li><i class="fas fa-check"></i> Sporty Design</li>
                    </ul>
                    <button class="vehicle-btn">View Details</button>
                </div>
            </div>

            <div class="vehicle-card">
                <div class="vehicle-image">
                    <i class="fas fa-car"></i>
                </div>
                <div class="vehicle-info">
                    <div class="vehicle-name">Outlander PHEV</div>
                    <div class="vehicle-price">Starting at $42,000</div>
                    <ul class="vehicle-features">
                        <li><i class="fas fa-check"></i> Plug-in Hybrid</li>
                        <li><i class="fas fa-check"></i> Electric Range 38 miles</li>
                        <li><i class="fas fa-check"></i> Eco-Friendly</li>
                        <li><i class="fas fa-check"></i> Tax Incentives Available</li>
                    </ul>
                    <button class="vehicle-btn">View Details</button>
                </div>
            </div>

            <div class="vehicle-card">
                <div class="vehicle-image">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="vehicle-info">
                    <div class="vehicle-name">L200 Pickup</div>
                    <div class="vehicle-price">Starting at $32,000</div>
                    <ul class="vehicle-features">
                        <li><i class="fas fa-check"></i> Double Cab Pickup</li>
                        <li><i class="fas fa-check"></i> 1-Tonne Payload</li>
                        <li><i class="fas fa-check"></i> Off-Road Capability</li>
                        <li><i class="fas fa-check"></i> Commercial Use Ready</li>
                    </ul>
                    <button class="vehicle-btn">View Details</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
