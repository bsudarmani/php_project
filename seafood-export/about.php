<?php
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<style>
    .about-page {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .hero-section {
        background: linear-gradient(135deg, #0a3147, #1b4b6c);
        color: white;
        padding: 80px 40px;
        border-radius: 20px;
        text-align: center;
        margin-bottom: 60px;
    }
    
    .hero-section h1 {
        font-size: 3rem;
        margin-bottom: 20px;
    }
    
    .hero-section p {
        font-size: 1.2rem;
        opacity: 0.95;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .mission-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 60px;
    }
    
    .mission-card {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .mission-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    
    .mission-card i {
        font-size: 4rem;
        color: #00d4ff;
        margin-bottom: 20px;
    }
    
    .mission-card h3 {
        color: #0a3147;
        margin-bottom: 15px;
    }
    
    .stats-section {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        margin: 60px 0;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-number {
        font-size: 3rem;
        font-weight: bold;
        color: #00d4ff;
    }
    
    .stat-label {
        color: #7f8c8d;
        font-size: 1.1rem;
    }
    
    .values-section {
        margin: 60px 0;
    }
    
    .values-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-top: 40px;
    }
    
    .value-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .value-card i {
        font-size: 3rem;
        color: #00d4ff;
        margin-bottom: 15px;
    }
    
    .value-card h4 {
        color: #0a3147;
        margin-bottom: 10px;
    }
    
    .certifications {
        background: #f8f9fa;
        padding: 60px 40px;
        border-radius: 15px;
        margin: 60px 0;
    }
    
    .cert-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }
    
    .cert-item {
        text-align: center;
    }
    
    .cert-item i {
        font-size: 3rem;
        color: #00d4ff;
        margin-bottom: 10px;
    }
    
    .team-section {
        margin: 60px 0;
    }
    
    .team-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-top: 40px;
    }
    
    .team-member {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .member-image {
        height: 200px;
        background: linear-gradient(135deg, #0a3147, #1b4b6c);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .member-image i {
        font-size: 5rem;
        color: #00d4ff;
    }
    
    .member-info {
        padding: 20px;
    }
    
    .member-info h4 {
        color: #0a3147;
        margin-bottom: 5px;
    }
    
    .member-info p {
        color: #7f8c8d;
        font-size: 0.9rem;
    }
    
    @media (max-width: 768px) {
        .mission-section,
        .values-grid,
        .team-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-section {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="about-page">
    <div class="hero-section">
        <h1>About SeaFood Export System</h1>
        <p>Leading exporter of premium quality seafood, committed to sustainable fishing practices and global food safety standards since 2010.</p>
    </div>
    
    <div class="mission-section">
        <div class="mission-card">
            <i class="fas fa-bullseye"></i>
            <h3>Our Mission</h3>
            <p>To provide the world with the finest quality seafood while ensuring sustainable fishing practices and supporting local fishing communities.</p>
        </div>
        <div class="mission-card">
            <i class="fas fa-eye"></i>
            <h3>Our Vision</h3>
            <p>To become the most trusted seafood exporter globally, known for uncompromising quality, traceability, and environmental responsibility.</p>
        </div>
    </div>
    
    <div class="stats-section">
        <div class="stat-item">
            <div class="stat-number">15+</div>
            <div class="stat-label">Years of Excellence</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">25+</div>
            <div class="stat-label">Export Countries</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">5000+</div>
            <div class="stat-label">Tons Exported</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">100%</div>
            <div class="stat-label">HACCP Certified</div>
        </div>
    </div>
    
    <div class="values-section">
        <h2 style="text-align: center; color: #0a3147;">Our Core Values</h2>
        <div class="values-grid">
            <div class="value-card">
                <i class="fas fa-leaf"></i>
                <h4>Sustainability</h4>
                <p>Committed to responsible fishing practices and marine conservation.</p>
            </div>
            <div class="value-card">
                <i class="fas fa-certificate"></i>
                <h4>Quality First</h4>
                <p>Rigorous quality control at every stage of processing.</p>
            </div>
            <div class="value-card">
                <i class="fas fa-hand-holding-heart"></i>
                <h4>Community Support</h4>
                <p>Empowering local fishing communities through fair trade.</p>
            </div>
        </div>
    </div>
    
    <div class="certifications">
        <h2 style="text-align: center; color: #0a3147;">Our Certifications</h2>
        <div class="cert-grid">
            <div class="cert-item">
                <i class="fas fa-check-circle"></i>
                <h4>HACCP</h4>
                <p>Hazard Analysis Critical Control Point</p>
            </div>
            <div class="cert-item">
                <i class="fas fa-check-circle"></i>
                <h4>BRC</h4>
                <p>British Retail Consortium</p>
            </div>
            <div class="cert-item">
                <i class="fas fa-check-circle"></i>
                <h4>MSC</h4>
                <p>Marine Stewardship Council</p>
            </div>
            <div class="cert-item">
                <i class="fas fa-check-circle"></i>
                <h4>FDA</h4>
                <p>US Food and Drug Administration</p>
            </div>
        </div>
    </div>
    
    <div class="team-section">
        <h2 style="text-align: center; color: #0a3147;">Our Leadership Team</h2>
        <div class="team-grid">
            <div class="team-member">
                <div class="member-image">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="member-info">
                    <h4>Rajesh Kumar</h4>
                    <p>Founder & CEO</p>
                </div>
            </div>
            <div class="team-member">
                <div class="member-image">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="member-info">
                    <h4>Priya Sharma</h4>
                    <p>Quality Control Director</p>
                </div>
            </div>
            <div class="team-member">
                <div class="member-image">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="member-info">
                    <h4>Amit Patel</h4>
                   