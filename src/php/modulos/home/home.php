<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>KION | Gestión para Veterinarias</title>

    <style>

        /* =====================================================
           CONFIGURACIÓN GENERAL
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #fffaf6;
            color: #38281f;
        }

        a {
            text-decoration: none;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {
            width: 100%;
            height: 76px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 7%;

            background: #fffaf6;

            border-bottom: 1px solid #eee1d6;

            position: sticky;
            top: 0;

            z-index: 1000;
        }


        .logo {
            font-size: 31px;
            font-weight: 800;

            letter-spacing: 2px;

            color: #563827;
        }

        .logo span {
            color: #a86f4b;
        }


        .menu {
            display: flex;
            align-items: center;

            gap: 28px;
        }

        .menu a {
            color: #59473b;

            font-size: 14px;

            transition: .3s;
        }

        .menu a:hover {
            color: #a86f4b;
        }


        .btn-login {
            border: 1px solid #8d6045;

            padding: 10px 20px;

            border-radius: 8px;
        }

        .btn-login:hover {
            background: #8d6045;

            color: white !important;
        }


        /* =====================================================
           BOTÓN MODO OSCURO
        ===================================================== */

        .dark-mode-btn {
            border: 1px solid #8d6045;

            background: #fffaf6;

            color: #563827;

            width: 42px;
            height: 42px;

            border-radius: 50%;

            cursor: pointer;

            font-size: 18px;

            display: flex;

            align-items: center;

            justify-content: center;

            transition: .3s;
        }


        .dark-mode-btn:hover {
            background: #8d6045;

            color: white;

            transform: scale(1.08);
        }

        .language-btn {
            border: 1px solid #8d6045;
            background: #fffaf6;
            color: #563827;
            min-width: 42px;
            height: 42px;
            padding: 0 10px;
            border-radius: 21px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            transition: .3s;
        }

        .language-btn:hover {
            background: #8d6045;
            color: white;
        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero {
            min-height: 690px;

            padding: 85px 7%;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 60px;

            background:
                radial-gradient(
                    circle at 85% 20%,
                    #ead5c3,
                    transparent 30%
                ),
                #fffaf6;
        }


        .hero-content {
            width: 52%;
        }


        .tag {
            display: inline-block;

            padding: 9px 17px;

            background: #f0dfd2;

            color: #85583e;

            border-radius: 30px;

            font-size: 13px;

            font-weight: bold;

            margin-bottom: 24px;
        }


        .hero h1 {
            font-size: 61px;

            line-height: 1.05;

            margin-bottom: 25px;

            color: #38271e;
        }


        .hero h1 span {
            color: #a76d48;
        }


        .hero p {
            max-width: 620px;

            color: #75665c;

            font-size: 19px;

            line-height: 1.7;

            margin-bottom: 32px;
        }


        .hero-buttons {
            display: flex;

            gap: 15px;
        }


        .btn-primary {
            display: inline-block;

            background: #65432f;

            color: white;

            padding: 15px 26px;

            border-radius: 9px;

            font-weight: bold;

            transition: .3s;
        }


        .btn-primary:hover {
            background: #4d3223;

            transform: translateY(-3px);
        }


        .btn-secondary {
            display: inline-block;

            padding: 15px 26px;

            border: 1px solid #bfa794;

            color: #684735;

            border-radius: 9px;

            transition: .3s;
        }


        .btn-secondary:hover {
            background: #f0e2d7;
        }


        /* =====================================================
           MOCKUP PRINCIPAL
        ===================================================== */

        .hero-visual {
            width: 48%;

            display: flex;

            justify-content: center;
        }


        .dashboard {
            width: 530px;

            background: white;

            padding: 21px;

            border-radius: 21px;

            border: 1px solid #eaded5;

            box-shadow:
                0 30px 70px rgba(65, 42, 28, .18);

            transform: rotate(1.5deg);

            transition: .4s;
        }


        .dashboard:hover {
            transform: rotate(0deg) translateY(-5px);
        }


        .dashboard-top {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;
        }


        .dashboard-title {
            font-size: 18px;

            font-weight: bold;

            color: #4d3526;
        }


        .dashboard-user {
            width: 38px;
            height: 38px;

            border-radius: 50%;

            background: #d3a17d;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;
        }


        .dashboard-stats {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 12px;

            margin-bottom: 18px;
        }


        .stat {
            padding: 16px;

            background: #f7eee8;

            border-radius: 12px;
        }


        .stat small {
            color: #89786d;
        }


        .stat strong {
            display: block;

            font-size: 20px;

            margin-top: 8px;

            color: #4c3426;
        }


        .dashboard-content {
            display: grid;

            grid-template-columns:
                1.4fr 1fr;

            gap: 15px;
        }


        .chart {
            height: 190px;

            background: #faf5f1;

            border-radius: 13px;

            padding: 17px;
        }


        .chart h4 {
            margin-bottom: 20px;

            color: #5b4030;
        }


        .bars {
            height: 125px;

            display: flex;

            align-items: end;

            justify-content: space-around;
        }


        .bar {
            width: 26px;

            background: #a9704e;

            border-radius: 5px 5px 0 0;
        }


        .bar1 {
            height: 40%;
        }

        .bar2 {
            height: 65%;
        }

        .bar3 {
            height: 50%;
        }

        .bar4 {
            height: 82%;
        }

        .bar5 {
            height: 94%;
        }


        .inventory {
            background: #faf5f1;

            border-radius: 13px;

            padding: 17px;
        }


        .inventory h4 {
            margin-bottom: 14px;
        }


        .inventory-item {
            display: flex;

            justify-content: space-between;

            padding: 10px 0;

            border-bottom: 1px solid #e9ded5;

            font-size: 12px;
        }


        .available {
            color: #76935f;

            font-weight: bold;
        }


        /* =====================================================
           FRASE
        ===================================================== */

        .intro {
            text-align: center;

            padding: 35px 7% 80px;

            color: #86766b;

            font-size: 15px;
        }


        .intro strong {
            color: #644634;
        }


        /* =====================================================
           FUNCIONES
        ===================================================== */

        .features-section {
            padding: 110px 7%;

            background: white;

            text-align: center;
        }


        .section-label {
            color: #a76d48;

            font-size: 13px;

            font-weight: bold;

            letter-spacing: 1.5px;

            margin-bottom: 14px;
        }


        .section-title {
            font-size: 43px;

            color: #38291f;

            margin-bottom: 18px;
        }


        .section-description {
            max-width: 680px;

            margin: auto;

            color: #77685e;

            line-height: 1.7;

            font-size: 17px;
        }


        .features-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

            margin-top: 60px;

            text-align: left;
        }


        .feature-card {
            padding: 28px;

            border-radius: 17px;

            border: 1px solid #eee2d8;

            background: #fffdfb;

            transition: .3s;
        }


        .feature-card:hover {
            transform: translateY(-8px);

            box-shadow:
                0 15px 35px rgba(76, 48, 31, .10);
        }


        .feature-icon {
            width: 54px;
            height: 54px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f0dfd2;

            border-radius: 14px;

            font-size: 25px;

            margin-bottom: 20px;
        }


        .feature-card h3 {
            color: #4b3427;

            margin-bottom: 10px;
        }


        .feature-card p {
            color: #796b61;

            font-size: 14px;

            line-height: 1.6;
        }


        /* =====================================================
           SECCIÓN VETERINARIA
        ===================================================== */

        .vet-section {
            padding: 110px 7%;

            display: flex;

            align-items: center;

            gap: 80px;

            background: #f5eee8;
        }


        .vet-text {
            flex: 1;
        }


        .vet-text h2 {
            font-size: 43px;

            line-height: 1.15;

            color: #392a20;

            margin-bottom: 22px;
        }


        .vet-text p {
            color: #76675c;

            font-size: 17px;

            line-height: 1.8;

            margin-bottom: 25px;
        }


        .check-list {
            list-style: none;
        }


        .check-list li {
            margin: 15px 0;

            color: #594538;

            font-size: 15px;
        }


        .vet-visual {
            flex: 1;

            min-height: 360px;

            border-radius: 25px;

            background:
                linear-gradient(
                    135deg,
                    #60402e,
                    #b27b57
                );

            display: flex;

            align-items: center;

            justify-content: center;

            position: relative;

            overflow: hidden;
        }


        .paw {
            position: absolute;

            font-size: 100px;

            opacity: .10;
        }


        .paw-one {
            top: 20px;
            left: 30px;
        }


        .paw-two {
            bottom: 15px;
            right: 35px;
        }


        .vet-card {
            width: 76%;

            background: white;

            border-radius: 17px;

            padding: 25px;

            box-shadow:
                0 18px 40px rgba(0,0,0,.18);

            z-index: 2;
        }


        .vet-card-header {
            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 20px;
        }


        .vet-icon {
            width: 42px;
            height: 42px;

            border-radius: 11px;

            background: #ead7c8;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;
        }


        .vet-card h3 {
            color: #503827;
        }


        .vet-row {
            display: flex;

            justify-content: space-between;

            padding: 13px 0;

            border-bottom: 1px solid #eee2d9;

            font-size: 14px;
        }


        /* =====================================================
           VENTAS
        ===================================================== */

        .sales-section {
            padding: 110px 7%;

            display: flex;

            align-items: center;

            gap: 80px;

            background: #fffaf6;
        }


        .sales-visual {
            flex: 1;

            min-height: 360px;

            background: #3f2b20;

            border-radius: 25px;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px;
        }


        .sale-screen {
            width: 88%;

            background: white;

            padding: 25px;

            border-radius: 17px;
        }


        .sale-screen h3 {
            margin-bottom: 20px;

            color: #503827;
        }


        .sale-item {
            display: flex;

            justify-content: space-between;

            padding: 12px 0;

            border-bottom: 1px solid #eee2d8;

            font-size: 14px;
        }


        .sale-total {
            display: flex;

            justify-content: space-between;

            margin-top: 18px;

            font-size: 18px;

            font-weight: bold;

            color: #60412e;
        }


        .sales-text {
            flex: 1;
        }


        .sales-text h2 {
            font-size: 43px;

            margin-bottom: 20px;

            color: #392a20;
        }


        .sales-text p {
            color: #76675d;

            font-size: 17px;

            line-height: 1.8;

            margin-bottom: 25px;
        }


        /* =====================================================
           INVENTARIO
        ===================================================== */

        .inventory-section {
            padding: 110px 7%;

            display: flex;

            align-items: center;

            gap: 80px;

            background: #f5eee8;
        }


        .inventory-text {
            flex: 1;
        }


        .inventory-text h2 {
            font-size: 43px;

            margin-bottom: 20px;
        }


        .inventory-text p {
            color: #76675d;

            font-size: 17px;

            line-height: 1.8;

            margin-bottom: 25px;
        }


        .inventory-visual {
            flex: 1;

            min-height: 360px;

            background: white;

            border-radius: 25px;

            padding: 30px;

            box-shadow:
                0 15px 40px rgba(70,45,30,.10);
        }


        .inventory-table {
            width: 100%;
        }


        .table-header,
        .table-row {
            display: grid;

            grid-template-columns:
                2fr 1fr 1fr;

            gap: 10px;

            padding: 15px 5px;

            border-bottom: 1px solid #eee2d8;

            font-size: 14px;
        }


        .table-header {
            font-weight: bold;

            color: #694834;
        }


        .stock-good {
            color: #71915c;

            font-weight: bold;
        }


        .stock-low {
            color: #b56d55;

            font-weight: bold;
        }


        /* =====================================================
           SUCURSALES
        ===================================================== */

        .branches {
            padding: 110px 7%;

            background: white;

            text-align: center;
        }


        .branches-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 25px;

            margin-top: 55px;
        }


        .branch {
            padding: 30px;

            border-radius: 18px;

            border: 1px solid #eee1d7;

            background: #fffdfb;

            text-align: left;

            transition: .3s;
        }


        .branch:hover {
            transform: translateY(-6px);

            box-shadow:
                0 15px 30px rgba(70,45,30,.10);
        }


        .branch-icon {
            font-size: 32px;

            margin-bottom: 18px;
        }


        .branch h3 {
            margin-bottom: 10px;
        }


        .branch p {
            color: #76675d;

            line-height: 1.6;

            font-size: 14px;
        }


        /* =====================================================
           PASOS
        ===================================================== */

        .steps {
            padding: 110px 7%;

            text-align: center;

            background: #fffaf6;
        }


        .steps-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 30px;

            margin-top: 55px;
        }


        .step {
            background: white;

            padding: 35px;

            border-radius: 18px;

            border: 1px solid #eee1d7;
        }


        .step-number {
            font-size: 47px;

            font-weight: 800;

            color: #bb835e;

            margin-bottom: 15px;
        }


        .step h3 {
            margin-bottom: 12px;
        }


        .step p {
            color: #76675d;

            line-height: 1.6;
        }


        /* =====================================================
           BENEFICIOS
        ===================================================== */

        .benefits {
            padding: 100px 7%;

            background: #eee2d8;

            text-align: center;
        }


        .benefits-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

            margin-top: 50px;
        }


        .benefit {
            padding: 25px;
        }


        .benefit strong {
            display: block;

            font-size: 21px;

            color: #513827;

            margin-bottom: 10px;
        }


        .benefit p {
            color: #74645a;

            font-size: 14px;

            line-height: 1.6;
        }


        /* =====================================================
           CTA
        ===================================================== */

        .cta {
            margin: 80px 7%;

            padding: 85px 30px;

            border-radius: 28px;

            background:
                linear-gradient(
                    135deg,
                    #3c291f,
                    #714b34
                );

            color: white;

            text-align: center;
        }


        .cta h2 {
            font-size: 43px;

            margin-bottom: 18px;
        }


        .cta p {
            color: #e4d7ce;

            font-size: 17px;

            margin-bottom: 30px;
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        footer {
            padding: 55px 7%;

            background: #291d17;

            color: white;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        footer p {
            color: #bcaea5;

            font-size: 14px;
        }


        /* =====================================================
           MODO OSCURO
        ===================================================== */

        body.dark-mode {
            background: #171311;
            color: #eeeeee;
        }


        /* NAVBAR */

        body.dark-mode .navbar {
            background: #211b18;
            border-bottom: 1px solid #3b302a;
        }


        body.dark-mode .logo {
            color: #e4c6b0;
        }


        body.dark-mode .logo span {
            color: #c18b67;
        }


        body.dark-mode .menu a {
            color: #ded7d2;
        }


        body.dark-mode .menu a:hover {
            color: #d09a75;
        }


        body.dark-mode .btn-login {
            border-color: #a87959;
        }


        body.dark-mode .dark-mode-btn {
            background: #30251f;
            color: #f3d4bc;
            border-color: #a87959;
        }


        /* HERO */

        body.dark-mode .hero {
            background:
                radial-gradient(
                    circle at 85% 20%,
                    #493428,
                    transparent 30%
                ),
                #171311;
        }


        body.dark-mode .tag {
            background: #3b2b23;
            color: #d9a984;
        }


        body.dark-mode .hero h1 {
            color: #f1e9e4;
        }


        body.dark-mode .hero h1 span {
            color: #d19a74;
        }


        body.dark-mode .hero p {
            color: #c0b7b1;
        }


        body.dark-mode .btn-secondary {
            color: #e1b99b;
            border-color: #80604c;
        }


        body.dark-mode .btn-secondary:hover {
            background: #3a2b23;
        }


        /* DASHBOARD */

        body.dark-mode .dashboard {
            background: #24201e;
            border-color: #403731;

            box-shadow:
                0 30px 70px rgba(0, 0, 0, .45);
        }


        body.dark-mode .dashboard-title {
            color: #eee4dd;
        }


        body.dark-mode .stat {
            background: #302925;
        }


        body.dark-mode .stat small {
            color: #aaa09a;
        }


        body.dark-mode .stat strong {
            color: #eee4dd;
        }


        body.dark-mode .chart,
        body.dark-mode .inventory {
            background: #2b2522;
        }


        body.dark-mode .chart h4,
        body.dark-mode .inventory h4 {
            color: #e7ddd6;
        }


        body.dark-mode .inventory-item {
            border-color: #403832;
        }


        /* INTRO */

        body.dark-mode .intro {
            background: #171311;
            color: #aaa19b;
        }


        body.dark-mode .intro strong {
            color: #d2aa8e;
        }


        /* FUNCIONES */

        body.dark-mode .features-section {
            background: #201b18;
        }


        body.dark-mode .section-title {
            color: #eee7e2;
        }


        body.dark-mode .section-description {
            color: #b9b0aa;
        }


        body.dark-mode .feature-card {
            background: #292421;
            border-color: #403731;
        }


        body.dark-mode .feature-card h3 {
            color: #e9dfd8;
        }


        body.dark-mode .feature-card p {
            color: #b4aaa4;
        }


        body.dark-mode .feature-icon {
            background: #3b2c24;
        }


        /* VETERINARIA */

        body.dark-mode .vet-section {
            background: #1b1715;
        }


        body.dark-mode .vet-text h2 {
            color: #eee6e1;
        }


        body.dark-mode .vet-text p {
            color: #b9b0aa;
        }


        body.dark-mode .check-list li {
            color: #d0c6c0;
        }


        body.dark-mode .vet-card {
            background: #292421;
        }


        body.dark-mode .vet-card h3 {
            color: #eee4dd;
        }


        body.dark-mode .vet-row {
            border-color: #413832;
            color: #d6cec8;
        }


        body.dark-mode .vet-icon {
            background: #49352a;
        }


        /* VENTAS */

        body.dark-mode .sales-section {
            background: #171311;
        }


        body.dark-mode .sale-screen {
            background: #292421;
        }


        body.dark-mode .sale-screen h3 {
            color: #eee4dd;
        }


        body.dark-mode .sale-item {
            border-color: #413832;
            color: #d6cec8;
        }


        body.dark-mode .sale-total {
            color: #d6a27d;
        }


        body.dark-mode .sales-text h2 {
            color: #eee6e1;
        }


        body.dark-mode .sales-text p {
            color: #b9b0aa;
        }


        /* INVENTARIO */

        body.dark-mode .inventory-section {
            background: #1b1715;
        }


        body.dark-mode .inventory-text h2 {
            color: #eee6e1;
        }


        body.dark-mode .inventory-text p {
            color: #b9b0aa;
        }


        body.dark-mode .inventory-visual {
            background: #292421;

            box-shadow:
                0 15px 40px rgba(0,0,0,.35);
        }


        body.dark-mode .table-header {
            color: #d5a27e;
        }


        body.dark-mode .table-header,
        body.dark-mode .table-row {
            border-color: #413832;
        }


        body.dark-mode .table-row {
            color: #d5cec8;
        }


        /* SUCURSALES */

        body.dark-mode .branches {
            background: #201b18;
        }


        body.dark-mode .branch {
            background: #292421;
            border-color: #403731;
        }


        body.dark-mode .branch h3 {
            color: #eee4dd;
        }


        body.dark-mode .branch p {
            color: #b8aea8;
        }


        /* PASOS */

        body.dark-mode .steps {
            background: #171311;
        }


        body.dark-mode .step {
            background: #292421;
            border-color: #403731;
        }


        body.dark-mode .step h3 {
            color: #eee4dd;
        }


        body.dark-mode .step p {
            color: #b8aea8;
        }


        /* BENEFICIOS */

        body.dark-mode .benefits {
            background: #302620;
        }


        body.dark-mode .benefit strong {
            color: #eadbd1;
        }


        body.dark-mode .benefit p {
            color: #bdb2ab;
        }


        /* CTA */

        body.dark-mode .cta {
            background:
                linear-gradient(
                    135deg,
                    #211813,
                    #493024
                );
        }


        /* FOOTER */

        body.dark-mode footer {
            background: #0e0b0a;
        }


        body.dark-mode footer p {
            color: #aaa09a;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media(max-width: 1000px) {

            .menu {
                display: none;
            }


            .hero,
            .vet-section,
            .sales-section,
            .inventory-section {
                flex-direction: column;
            }


            .hero-content,
            .hero-visual,
            .vet-text,
            .sales-text,
            .inventory-text {
                width: 100%;
            }


            .hero {
                text-align: center;
            }


            .hero-buttons {
                justify-content: center;
            }


            .features-grid,
            .benefits-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }


            .branches-grid {
                grid-template-columns: 1fr;
            }
        }


        @media(max-width: 600px) {

            .hero h1 {
                font-size: 40px;
            }


            .section-title,
            .vet-text h2,
            .sales-text h2,
            .inventory-text h2,
            .cta h2 {
                font-size: 33px;
            }


            .features-grid,
            .benefits-grid,
            .steps-grid {
                grid-template-columns: 1fr;
            }


            .dashboard-stats {
                grid-template-columns: 1fr;
            }


            .dashboard-content {
                grid-template-columns: 1fr;
            }


            .hero-buttons {
                flex-direction: column;
            }


            footer {
                flex-direction: column;

                gap: 15px;

                text-align: center;
            }
        }


        /* =====================================================
           ANIMACIONES E ICONOS VECTORIALES CSS
        ===================================================== */
        .kion-icon {
            display: inline-block;
            vertical-align: middle;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), stroke 0.3s ease, fill 0.3s ease;
        }

        .kion-icon-float {
            animation: kionFloat 3.5s ease-in-out infinite;
        }

        .kion-icon-pulse {
            animation: kionPulse 2.8s ease-in-out infinite;
        }

        .kion-icon-spin-hover:hover {
            transform: rotate(20deg) scale(1.15);
        }

        @keyframes kionFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        @keyframes kionPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.08); opacity: 0.85; }
        }

        .feature-card .feature-icon, .branch .branch-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f4e8de, #ead5c3);
            color: #85583e;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            box-shadow: 0 4px 12px rgba(133, 88, 62, 0.12);
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .feature-card:hover .feature-icon, .branch:hover .branch-icon {
            background: linear-gradient(135deg, #85583e, #563827);
            color: #ffffff;
            transform: translateY(-4px) scale(1.08);
            box-shadow: 0 8px 22px rgba(86, 56, 39, 0.25);
        }

        .feature-card:hover .feature-icon svg, .branch:hover .branch-icon svg {
            stroke: #ffffff;
            transform: scale(1.1);
        }

        .check-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
            font-size: 15px;
            color: #59473b;
            transition: transform 0.22s ease, color 0.22s ease;
        }

        .check-list li:hover {
            transform: translateX(4px);
            color: #85583e;
        }

        .check-icon-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e4ebd9;
            color: #4a753c;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(74, 117, 60, 0.15);
            transition: background 0.25s ease, color 0.25s ease, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .check-list li:hover .check-icon-badge {
            background: #4a753c;
            color: #ffffff;
            transform: scale(1.15) rotate(5deg);
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .dark-mode-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0 14px;
            border-radius: 20px;
        }

    </style>
</head>

<body>

<!-- =====================================================
     NAVBAR
===================================================== -->
<header class="navbar">
    <div class="logo">
        KI<span>O</span>N
    </div>

    <nav class="menu">
        <a href="#inicio">Inicio</a>
        <a href="../../componentes/catalogo.php">Catálogo</a>

        <?php if (isset($_SESSION['usuario'])): ?>
            <a href="dashboard.php" class="btn-login" style="background: #8d6045; color: #ffffff !important; display: inline-flex; align-items: center; gap: 8px;">
                <svg class="kion-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                 <?= htmlspecialchars($_SESSION['usuario']['nombre'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <a href="cerrarSesion.php" style="color: #c94a4a; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
                <svg class="kion-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Cerrar sesión
            </a>
        <?php else: ?>
            <a href="../../../../inicioSesion.php" class="btn-login">Iniciar sesión</a>
        <?php endif; ?>

        <!-- BOTÓN MODO OSCURO -->
        <button id="darkModeBtn" class="dark-mode-btn" title="Cambiar modo">

            🌙

        </button>

        <button id="languageToggle" class="language-btn" type="button" aria-label="Cambiar idioma">
            EN
        </button>
    </nav>
</header>

<!-- =====================================================
     HERO
===================================================== -->
<section class="hero" id="inicio">
    <div class="hero-content">
        <div class="tag">
            <svg class="kion-icon kion-icon-pulse" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"></path><path d="M2 17l10 5 10-5"></path><path d="M2 12l10 5 10-5"></path></svg>
            <span>SOFTWARE PARA VETERINARIAS</span>
        </div>

        <h1>
            La administración de tu veterinaria,
            <span>más fácil.</span>
        </h1>

        <p>
            KION es un sistema de punto de venta e inventario diseñado para ayudarte a controlar las ventas, productos, usuarios y sucursales de tu negocio veterinario desde un solo lugar.
        </p>

        <div class="hero-buttons">
            <a href="../../../../inicioSesion.php" class="btn-primary">Comenzar ahora</a>
            <a href="#funciones" class="btn-secondary">Conocer KION</a>
        </div>
    </div>

    <!-- MOCKUP DEL SISTEMA -->
    <div class="hero-visual">
        <div class="dashboard">
            <div class="dashboard-top">
                <div class="dashboard-title">Panel de KION</div>
                <div class="dashboard-user">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                </div>
            </div>

            <div class="dashboard-stats">
                <div class="stat">
                    <small>Ventas hoy</small>
                    <strong>$24,580</strong>
                </div>
                <div class="stat">
                    <small>Productos</small>
                    <strong>328</strong>
                </div>
                <div class="stat">
                    <small>Sucursales</small>
                    <strong>4</strong>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="chart">
                    <h4>Ventas recientes</h4>
                    <div class="bars">
                        <div class="bar bar1"></div>
                        <div class="bar bar2"></div>
                        <div class="bar bar3"></div>
                        <div class="bar bar4"></div>
                        <div class="bar bar5"></div>
                    </div>
                </div>

                <div class="inventory">
                    <h4>Inventario</h4>
                    <div class="inventory-item">
                        <span>Alimentos</span>
                        <span class="available">125</span>
                    </div>
                    <div class="inventory-item">
                        <span>Medicamentos</span>
                        <span class="available">48</span>
                    </div>
                    <div class="inventory-item">
                        <span>Accesorios</span>
                        <span class="available">73</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =====================================================
     INTRO
===================================================== -->
<section class="intro">
    Una solución pensada para <strong>veterinarias que quieren tener el control de su negocio.</strong>
</section>

<!-- =====================================================
     FUNCIONES PRINCIPALES
===================================================== -->
<section class="features-section" id="funciones">
    <div class="section-label">TODO EN UN SOLO LUGAR</div>
    <h2 class="section-title">Todo lo que tu veterinaria necesita</h2>
    <p class="section-description">
        KION reúne las herramientas necesarias para administrar las operaciones de tu negocio de manera sencilla y organizada.
    </p>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <svg class="kion-icon kion-icon-float" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            </div>
            <h3>Punto de venta</h3>
            <p>Registra las ventas de alimentos, medicamentos, accesorios y productos veterinarios al instante.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <svg class="kion-icon kion-icon-float" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation-delay: 0.3s"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            </div>
            <h3>Gestión de Inventario</h3>
            <p>Mantén controladas las existencias y alertas de stock de tus productos en cada sucursal.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <svg class="kion-icon kion-icon-float" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation-delay: 0.6s"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path><path d="M9 9h1"></path><path d="M9 13h1"></path><path d="M9 17h1"></path></svg>
            </div>
            <h3>Multi-Sucursal</h3>
            <p>Administra diferentes sucursales y sedes centralizadamente desde un mismo sistema.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <svg class="kion-icon kion-icon-float" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation-delay: 0.9s"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <h3>Control de Personal</h3>
            <p>Gestiona los accesos y funciones de administradores, gerentes y cajeros.</p>
        </div>
    </div>
</section>

<!-- =====================================================
     DEMOSTRACIÓN DE VENTAS E INVENTARIO
===================================================== -->
<section class="sales-section" id="ventas">
    <div class="sales-visual">
        <div class="sale-screen">
            <h3>Punto de venta activo</h3>
            <div class="sale-item">
                <span>Alimento para perro (15kg)</span>
                <strong>$450.00</strong>
            </div>
            <div class="sale-item">
                <span>Antibiótico veterinario</span>
                <strong>$280.00</strong>
            </div>
            <div class="sale-item">
                <span>Collar antipulgas</span>
                <strong>$190.00</strong>
            </div>
            <div class="sale-total">
                <span>Total</span>
                <span>$920.00</span>
            </div>
        </div>
    </div>

    <div class="sales-text">
        <div class="section-label">PUNTO DE VENTA E INVENTARIO</div>
        <h2>Vende de forma rápida y mantén la información actualizada.</h2>
        <p>Registra cada operación al instante y consulta existencias en tiempo real sin complicaciones.</p>
        <ul class="check-list">
            <li>
                <div class="check-icon-badge"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                <span>Registro inmediato de ventas e ingresos</span>
            </li>
            <li>
                <div class="check-icon-badge"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                <span>Categorización de alimentos, medicamentos y accesorios</span>
            </li>
            <li>
                <div class="check-icon-badge"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                <span>Descuento automático de existencias en inventario</span>
            </li>
            <li>
                <div class="check-icon-badge"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                <span>Control por sucursal y reporte de operaciones</span>
            </li>
        </ul>
    </div>
</section>

<!-- =====================================================
     SUCURSALES Y ROLES
===================================================== -->
<section class="branches" id="sucursales">
    <div class="section-label">MULTI-SUCURSAL Y ROLES</div>
    <h2 class="section-title">Una sola plataforma para todas tus sucursales</h2>
    <p class="section-description">Centraliza la información y facilita la administración de cada punto de venta.</p>

    <div class="branches-grid">
        <div class="branch">
            <div class="branch-icon">
                <svg class="kion-icon kion-icon-pulse" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path></svg>
            </div>
            <h3>Sucursales</h3>
            <p>Administra los productos y existencias de cada sede de forma independiente.</p>
        </div>

        <div class="branch">
            <div class="branch-icon">
                <svg class="kion-icon kion-icon-pulse" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation-delay: 0.3s"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle><polygon points="12 11 14 15 10 15 12 11"></polygon></svg>
            </div>
            <h3>Gerentes</h3>
            <p>Los responsables de cada sede supervisan las operaciones y stock local.</p>
        </div>

        <div class="branch">
            <div class="branch-icon">
                <svg class="kion-icon kion-icon-pulse" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation-delay: 0.6s"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            </div>
            <h3>Cajeros</h3>
            <p>Realizan los cobros diarios y atienden el punto de venta de forma ágil.</p>
        </div>
    </div>
</section>

<!-- =====================================================
     PASOS
===================================================== -->
<section class="steps" id="nosotros">
    <div class="section-label">SIMPLE Y ORGANIZADO</div>
    <h2 class="section-title">Comienza a utilizar KION en 3 pasos</h2>
    <p class="section-description">Organiza las operaciones de tu veterinaria sin complicaciones.</p>

    <div class="steps-grid">
        <div class="step">
            <div class="step-number">01</div>
            <h3>Ingresa</h3>
            <p>Inicia sesión en tu cuenta para acceder al sistema.</p>
        </div>

        <div class="step">
            <div class="step-number">02</div>
            <h3>Administra</h3>
            <p>Controla inventario, existencias y permisos de cada sucursal.</p>
        </div>

        <div class="step">
            <div class="step-number">03</div>
            <h3>Vende</h3>
            <p>Registra ventas y mantén actualizada la información de tu negocio.</p>
        </div>
    </div>
</section>

<!-- =====================================================
     CTA
===================================================== -->
<section class="cta">
    <h2>Tu veterinaria necesita control.</h2>
    <p>Ventas, inventario y sucursales en un solo lugar.</p>
    <a href="../../../../inicioSesion.php" class="btn-primary">Comenzar con KION</a>
</section>

<!-- =====================================================
     FOOTER
===================================================== -->
<footer>
    <div class="logo">
        KI<span>O</span>N
    </div>
    <p>© 2026 KION · Punto de Venta e Inventario para Veterinarias</p>
</footer>

<!-- =====================================================
     JAVASCRIPT MODO OSCURO E IDIOMA
===================================================== -->
<script>
    const darkModeBtn = document.getElementById("darkModeBtn");
    darkModeBtn.addEventListener("click", function () {
        document.body.classList.toggle("dark-mode");
    });
</script>

<script>
    const homeTranslations = {
        'Inicio': 'Home', 'Catálogo': 'Catalog', 'Iniciar sesión': 'Sign in',
        'Perfil': 'Profile', 'Cerrar sesión': 'Sign out', 'SOFTWARE PARA VETERINARIAS': 'SOFTWARE FOR VETERINARY CLINICS',
        'La administración': 'Managing', 'de tu veterinaria,': 'your veterinary clinic,', 'más fácil.': 'made easier.',
        'Comenzar ahora': 'Get started', 'Conocer KION': 'Discover KION', 'Panel de KION': 'KION dashboard',
        'Ventas hoy': 'Today sales', 'Productos': 'Products', 'Sucursales': 'Branches',
        'Ventas recientes': 'Recent sales', 'Alimentos': 'Food', 'Medicamentos': 'Medicine', 'Accesorios': 'Accessories',
        'TODO EN UN SOLO LUGAR': 'EVERYTHING IN ONE PLACE',
        'Todo lo que tu veterinaria necesita': 'Everything your veterinary clinic needs',
        'Punto de venta': 'Point of sale', 'Gestión de Inventario': 'Inventory Management',
        'Multi-Sucursal': 'Multi-Branch', 'Control de Personal': 'Staff Control',
        'PUNTO DE VENTA E INVENTARIO': 'POINT OF SALE AND INVENTORY',
        'Vende de forma rápida y mantén la información actualizada.': 'Sell quickly and keep information up to date.',
        'MULTI-SUCURSAL Y ROLES': 'MULTI-BRANCH AND ROLES',
        'Una sola plataforma para todas tus sucursales': 'One platform for all your branches',
        'Gerentes': 'Managers', 'Cajeros': 'Cashiers',
        'SIMPLE Y ORGANIZADO': 'SIMPLE AND ORGANIZED',
        'Comienza a utilizar KION en 3 pasos': 'Start using KION in 3 steps',
        'Ingresa': 'Log in', 'Administra': 'Manage', 'Vende': 'Sell',
        'Tu veterinaria necesita control.': 'Your veterinary clinic needs control.',
        'Comenzar con KION': 'Get started with KION',
        'KION es un sistema de punto de venta e inventario diseñado para ayudarte a controlar las ventas, productos, usuarios y sucursales de tu negocio veterinario desde un solo lugar.': 'KION is a point-of-sale and inventory system designed to help you manage sales, products, users, and branches from one place.',
        '© 2026 KION · Punto de Venta e Inventario para Veterinarias': '© 2026 KION · Point of Sale and Inventory for Veterinary Clinics'
    };

    const originalHomeTexts = new Map();
    let homeInEnglish = false;

    function toggleHomeLanguage() {
        const entries = Object.entries(homeTranslations).sort((a, b) => b[0].length - a[0].length);
        document.querySelectorAll('body *:not(script):not(style)').forEach((element) => {
            element.childNodes.forEach((node) => {
                if (node.nodeType !== Node.TEXT_NODE || node.parentElement?.hasAttribute('data-i18n')) return;
                if (!originalHomeTexts.has(node)) originalHomeTexts.set(node, node.textContent);
                const originalText = originalHomeTexts.get(node);
                const leading = originalText.match(/^\s*/)[0];
                const trailing = originalText.match(/\s*$/)[0];
                const normalized = originalText.trim().replace(/\s+/g, ' ');
                if (!homeInEnglish) {
                    node.textContent = originalText;
                    return;
                }
                let translated = normalized;
                entries.forEach(([spanish, english]) => {
                    translated = translated.split(spanish).join(english);
                });
                node.textContent = `${leading}${translated}${trailing}`;
            });
        });
        document.documentElement.lang = homeInEnglish ? 'en' : 'es';
        document.getElementById('languageToggle').textContent = homeInEnglish ? 'ES' : 'EN';
    }

    document.getElementById('languageToggle').addEventListener('click', () => {
        homeInEnglish = !homeInEnglish;
        toggleHomeLanguage();
    });
</script>
</body>
</html>