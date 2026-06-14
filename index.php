<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homey - Ideal Diet Planner</title>
    <meta name="description" content="Get personalized, dynamic diet plans that adapt to your life. Build your plate, calculate calories, and achieve your dream body with Homey.">
    <!-- Link to the main stylesheet containing liquid glass & interactive selector rules -->
    <link rel="stylesheet" href="index.css">
    
    <!-- Google Fonts: Outfit (headings) and Inter (body copy) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <!-- 
      BACKGROUND COMPONENT: Liquid Glass Animated Backdrop
      Contains the colored blobs that animate using CSS keyframes.
      The glass-overlay frosted panel blends and blurs them together.
    -->
    <div class="liquid-bg">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="blob blob-4"></div>
        <div class="glass-overlay"></div>
    </div>

    <!-- 
      INTERACTIVE TRIGGER (TESTIMONIALS FILTER):
      These radio buttons are positioned at the root level so that the ~ sibling selector
      can scan downwards and control testimonial cards located within .page-wrapper.
    -->
    <input type="radio" id="filter-all" name="testimonial-filter" class="filter-radio" checked>
    <input type="radio" id="filter-loss" name="testimonial-filter" class="filter-radio">
    <input type="radio" id="filter-gain" name="testimonial-filter" class="filter-radio">

    <!-- 
      INTERACTIVE TRIGGER (MOBILE NAV MENU):
      Checking this input displays/hides the .mobile-drawer overlay using CSS sibling selection.
    -->
    <input type="checkbox" id="nav-toggle" class="nav-toggle-checkbox">

    <!-- 
      HEADER: Glassmorphic Floating Navigation Header
      Stays fixed at the top of the viewport.
    -->
    <header class="glass-header">
        <div class="nav-container container">
            <!-- Website Logo using the official Homey logo image -->
            <a href="#" class="logo">
                <img src="https://www.homey.com.my/images/Homey_logo.png" alt="Homey Logo" style="height: 35px; width: auto; object-fit: contain;">
            </a>

            <!-- Desktop Menu Options -->
            <nav class="nav-menu">
                <a href="#features" class="nav-link">Features</a>
                <a href="#about-us" class="nav-link">About Us</a>
                <a href="#testimonials" class="nav-link">Success Stories</a>
                <?php if (!$is_logged_in): ?>
                    <a href="login.php" class="nav-link">Sign In</a>
                <?php endif; ?>
            </nav>

            <!-- Desktop Call-To-Action Button -->
            <div class="nav-cta" style="display: flex; align-items: center; gap: 15px;">
                <?php if ($is_logged_in): ?>
                    <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-muted);">Hi, <?php echo htmlspecialchars($full_name); ?></span>
                    <a href="dashboard.php" class="btn btn-primary btn-sm">Go to Dashboard</a>
                    <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
                <?php else: ?>
                    <a href="signup.php" class="btn btn-primary btn-sm">Get Started</a>
                <?php endif; ?>
            </div>

            <!-- 
              MOBILE NAV HAMBURGER TRIGGER:
              A label matching the #nav-toggle input. Clicking this toggles the checkbox state.
            -->
            <label for="nav-toggle" class="nav-toggle-label">
                <span></span>
                <span></span>
                <span></span>
            </label>
        </div>
    </header>

    <!-- 
      MOBILE DRAWER MENU:
      An off-canvas nav drawer that slides in when #nav-toggle is checked.
      The inline onclick code unchecks the input to close the drawer automatically upon selection.
    -->
    <div class="mobile-drawer">
        <nav class="mobile-nav-links">
            <a href="#features" onclick="document.getElementById('nav-toggle').checked = false;">Features</a>
            <a href="#about-us" onclick="document.getElementById('nav-toggle').checked = false;">About Us</a>
            <a href="#testimonials" onclick="document.getElementById('nav-toggle').checked = false;">Success Stories</a>
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php" class="btn btn-primary" onclick="document.getElementById('nav-toggle').checked = false;">Go to Dashboard</a>
                <a href="logout.php" class="btn btn-outline" onclick="document.getElementById('nav-toggle').checked = false;">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline" onclick="document.getElementById('nav-toggle').checked = false;">Sign In</a>
                <a href="signup.php" class="btn btn-primary" onclick="document.getElementById('nav-toggle').checked = false;">Get Started</a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- 
      MAIN WRAPPER:
      Houses all page sections and serves as a sibling target for root filter controls.
    -->
    <div class="page-wrapper">

        <!-- HERO SECTION: Hook messaging + Static Dashboard Graphic -->
        <section class="hero-section container">
            <div class="hero-grid">
                <!-- Hero messaging column -->
                <div class="hero-content">
                    <h1>Nutrition that adapts to your life, not the other way around.</h1>
                    <p class="lead">Get custom-tailored meal schedules, smart macro-balancing, and local grocery integration—all responsive to your body and cravings.</p>
                    <div class="hero-buttons">
                        <?php if ($is_logged_in): ?>
                            <a href="dashboard.php" class="btn btn-primary">Get Started</a>
                        <?php else: ?>
                            <a href="signup.php" class="btn btn-primary">Get Started</a>
                        <?php endif; ?>
                        <a href="#about-us" class="btn btn-outline">About Us</a>
                    </div>
                </div>

                <!-- Hero visual column representing the diet planner dashboard UI -->
                <div class="hero-mockup-wrapper">
                    <div class="glass-card hero-mockup">
                        <div class="mockup-header">
                            <span class="dot red"></span>
                            <span class="dot yellow"></span>
                            <span class="dot green"></span>
                            <span class="mockup-title">Homey Dashboard Preview</span>
                        </div>
                        <div class="mockup-body">
                            <!-- Active Goal Banner -->
                            <div class="mock-goal-banner">
                                <span class="mock-goal-icon">🎯</span>
                                <div class="mock-goal-text">
                                    <div class="mock-goal-title">Calorie Deficit Active — 1,850 kcal/day</div>
                                    <div class="mock-goal-sub">1,430 kcal remaining today · TDEE: 2,250 kcal</div>
                                </div>
                            </div>

                            <!-- Metrics Grid -->
                            <div class="mock-metrics-grid">
                                <div class="mock-metric-card">
                                    <div class="mock-metric-lbl">Intake</div>
                                    <div class="mock-metric-val">420 <small>kcal</small></div>
                                    <div class="mock-bar-wrap"><div class="mock-bar mock-bar-g" style="width: 23%"></div></div>
                                </div>
                                <div class="mock-metric-card">
                                    <div class="mock-metric-lbl">Water today</div>
                                    <div class="mock-metric-val" style="color:#3B82F6">1.5 <small>L</small></div>
                                    <div class="mock-bar-wrap"><div class="mock-bar mock-bar-b" style="width: 60%"></div></div>
                                </div>
                                <div class="mock-metric-card">
                                    <div class="mock-metric-lbl">Daily Target</div>
                                    <div class="mock-metric-val">1,850 <small>kcal</small></div>
                                    <div class="mock-bar-wrap"><div class="mock-bar mock-bar-g" style="width: 100%"></div></div>
                                </div>
                                <div class="mock-metric-card">
                                    <div class="mock-metric-lbl">Meals Logged</div>
                                    <div class="mock-metric-val">1 <small>/ 4</small></div>
                                    <div class="mock-bar-wrap"><div class="mock-bar mock-bar-a" style="width: 25%"></div></div>
                                </div>
                            </div>

                            <!-- Today's Meal Log -->
                            <div class="mock-meal-log-title">Today's Meal Log</div>
                            <div class="mock-meal-list">
                                <div class="mock-meal-entry">
                                    <div>
                                        <div class="mock-meal-name">Avocado Toast & Poached Egg</div>
                                        <div class="mock-meal-meta">Breakfast · 08:00 AM</div>
                                    </div>
                                    <div class="mock-meal-cal">420 kcal</div>
                                </div>
                                <div class="mock-meal-entry">
                                    <div>
                                        <div class="mock-meal-name">Grilled Salmon & Quinoa Bowl</div>
                                        <div class="mock-meal-meta">Lunch · 01:00 PM</div>
                                    </div>
                                    <div class="mock-meal-cal">650 kcal</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES SECTION: Core values grids -->
        <section id="features" class="features-section container">
            <div class="section-header">
                <span class="section-tag">Powerful Features</span>
                <h2>What Homey Offers</h2>
                <p>Designed with procedural simplicity to make tracking your metrics intuitive and straightforward.</p>
            </div>
            <!-- Grid cards displaying main system features -->
            <div class="features-grid">
                <div class="glass-card feature-card">
                    <div class="feature-icon bg-emerald">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <h3>Personalized Dashboard</h3>
                    <p>Get real-time calorie goals computed using Mifflin-St Jeor formulas based on your age, height, and activity level.</p>
                </div>

                <div class="glass-card feature-card">
                    <div class="feature-icon bg-orange">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                    </div>
                    <h3>Calorie & Intake Tracker</h3>
                    <p>Easily log daily meals (Breakfast, Lunch, Dinner, Snacks) and automatically calculate protein, fat, and carbohydrate ratios.</p>
                </div>

                <div class="glass-card feature-card">
                    <div class="feature-icon bg-mint">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C9 6.5 6 10 6 14a6 6 0 0 0 12 0c0-4-3-7.5-6-12z"/></svg>
                    </div>
                    <h3>Hydration Logs</h3>
                    <p>Record your daily cups of water (250ml each) and view historical logs to build a healthy hydration routine.</p>
                </div>

                <div class="glass-card feature-card">
                    <div class="feature-icon bg-orange">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 10h16M4 14h10"/></svg>
                    </div>
                    <h3>Community Recipes</h3>
                    <p>Share customized culinary guides with the community, explore ideas approved by administrators, and log meals with a single click.</p>
                </div>
            </div>
        </section>

        <!-- ABOUT US SECTION: Team & Story -->
        <section id="about-us" class="about-section container" style="padding: 5rem 0;">
            <div class="section-header">
                <span class="section-tag">About Homey</span>
                <h2>Dedicated to your nutritional success</h2>
                <p>We believe that healthy living should be intuitive, accessible, and scientifically grounded.</p>
            </div>
            
            <div class="glass-card" style="padding: 3.5rem; max-width: 800px; margin: 0 auto 4rem auto; border-radius: 24px;">
                <h3 style="font-size: 2rem; margin-bottom: 1.5rem; color: var(--text-main); text-align: center; font-family: var(--font-heading);">Our Story</h3>
                <p style="margin-bottom: 1.5rem; line-height: 1.8; font-size: 1.05rem; text-align: justify; color: var(--text-main);">Homey was founded to bridge the gap between strict clinical science and busy daily routines. By using personalized physiological models, Homey helps individuals achieve their ideal weight and hydration targets without feeling restricted.</p>
                <p style="line-height: 1.8; font-size: 1.05rem; text-align: justify; color: var(--text-main); margin-bottom: 0;">Whether you are looking to lose body fat, build lean muscle mass, or maintain a healthy metabolic baseline, our planner adapts to your actual inputs and supports your journey every step of the way.</p>
            </div>

            <!-- Leadership Team Sub-section -->
            <div class="team-section">
                <div class="section-header" style="margin-bottom: 2.5rem;">
                    <span class="section-tag">Our Team</span>
                    <h2>Meet our experts</h2>
                    <p>A professional coalition of medical doctors, dietitians, and technologists.</p>
                </div>
                <div class="team-grid">
                    <!-- Member 1 -->
                    <div class="glass-card team-card">
                        <div class="team-img-wrapper">
                            <img src="images/suhayl.png" alt="Mohamad Suhayl Alwan">
                        </div>
                        <h3>Mohamad Suhayl Alwan</h3>
                        <span class="position">Co-Founder & CEO</span>
                        <p class="bio">Co-Founder and CEO passionate about evidence-based digital health solutions and community wellness development.</p>
                    </div>
                    <!-- Member 2 -->
                    <div class="glass-card team-card">
                        <div class="team-img-wrapper">
                            <img src="images/amjad.png" alt="Amjad Irfan">
                        </div>
                        <h3>Amjad Irfan</h3>
                        <span class="position">Certified Dietitian</span>
                        <p class="bio">Certified dietitian specializing in personalized nutrition protocols, dietary tracking, and wellness counseling.</p>
                    </div>
                    <!-- Member 3 -->
                    <div class="glass-card team-card">
                        <div class="team-img-wrapper">
                            <img src="images/azrul.png" alt="Muhammad Azrul">
                        </div>
                        <h3>Muhammad Azrul</h3>
                        <span class="position">Medical Doctor</span>
                        <p class="bio">Medical doctor specializing in primary care, lifestyle medicine, and clinical verification of health metrics.</p>
                    </div>
                    <!-- Member 4 -->
                    <div class="glass-card team-card">
                        <div class="team-img-wrapper">
                            <img src="images/ammar.png" alt="Ammar Syahmi">
                        </div>
                        <h3>Ammar Syahmi</h3>
                        <span class="position">Lead Technologist</span>
                        <p class="bio">Lead technologist focusing on intuitive system architecture, interactive web design, and digital accessibility.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 
          TESTIMONIALS SECTION: 
          Success stories filter system driven by root radio buttons (`filter-radio`).
        -->
        <section id="testimonials" class="testimonials-section container">
            <div class="section-header">
                <span class="section-tag">Success Stories</span>
                <h2>Real stories. Real metrics.</h2>
                <p>Filter testimonials below to see how users with your target goals achieved success.</p>
            </div>

            <!-- Filters (Labels triggering root level radio inputs) -->
            <div class="testimonial-filters">
                <label for="filter-all" class="filter-btn all-btn">All Stories</label>
                <label for="filter-loss" class="filter-btn loss-btn">Weight Loss</label>
                <label for="filter-gain" class="filter-btn gain-btn">Muscle Gain</label>
            </div>

            <!-- Review Cards Grid -->
            <div class="stories-grid">
                <!-- Siti Aminah: Weight Loss Testimonial -->
                <div class="glass-card story-card loss">
                    <div class="story-user">
                        <div>
                            <h4>Siti Aminah</h4>
                            <span class="user-meta">Lost 12 kg in 4 Months</span>
                        </div>
                    </div>
                    <div class="rating">⭐⭐⭐⭐⭐</div>
                    <p class="story-text">"Very helpful web for losing weight. I successfully lost weight and became healthier."</p>
                    <span class="story-badge tag-green">Fat Loss Goal</span>
                </div>

                <!-- Marcus Lim: Muscle Gain Testimonial -->
                <div class="glass-card story-card gain">
                    <div class="story-user">
                        <div>
                            <h4>Marcus Lim</h4>
                            <span class="user-meta">Gained 6 kg lean muscle</span>
                        </div>
                    </div>
                    <div class="rating">⭐⭐⭐⭐⭐</div>
                    <p class="story-text">"Great system for muscle building. It helps me easily manage my daily calories."</p>
                    <span class="story-badge tag-orange">Muscle Gain Goal</span>
                </div>

                <!-- Dr. Elena Low: Weight Loss Testimonial -->
                <div class="glass-card story-card loss">
                    <div class="story-user">
                        <div>
                            <h4>Dr. Elena Low</h4>
                            <span class="user-meta">Consistent energy levels</span>
                        </div>
                    </div>
                    <div class="rating">⭐⭐⭐⭐⭐</div>
                    <p class="story-text">"Very accurate calorie calculator. It is very useful for patient diet references."</p>
                    <span class="story-badge tag-green">Fat Loss Goal</span>
                </div>

                <!-- David Kumar: Muscle Gain Testimonial -->
                <div class="glass-card story-card gain">
                    <div class="story-user">
                        <div>
                            <h4>David Kumar</h4>
                            <span class="user-meta">Gained 8 kg muscle mass</span>
                        </div>
                    </div>
                    <div class="rating">⭐⭐⭐⭐⭐</div>
                    <p class="story-text">"Easy to log water intake every day. It helps me make sure I drink enough water."</p>
                    <span class="story-badge tag-orange">Muscle Gain Goal</span>
                </div>
            </div>
        </section>

        <!-- FOOTER: Branding, links column, and copy notices -->
        <footer class="footer-area">
            <div class="footer-container container">
                <div class="footer-brand">
                    <div class="logo">
                        <img src="https://www.homey.com.my/images/Homey_logo.png" alt="Homey Logo" style="height: 30px; width: auto; object-fit: contain;">
                    </div>
                    <p>Building high-fidelity tools to integrate balanced science into daily habits.</p>
                </div>
                <div class="footer-links">
                    <div class="link-col">
                        <h4>Product</h4>
                        <a href="#features">Features</a>
                        <a href="#about-us">About Us</a>
                        <a href="#testimonials">Success Stories</a>
                    </div>
                    <div class="link-col">
                        <h4>System Features</h4>
                        <a href="calories.php">Calorie Tracker</a>
                        <a href="hydration.php">Hydration Tracker</a>
                        <a href="recipes.php">Recipes Library</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom container">
                <p>&copy; 2026 Homey Systems. All rights reserved.</p>
            </div>
        </footer>

    </div>

</body>
</html>
