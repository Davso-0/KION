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

        <a href="#inicio">
            Inicio
        </a>

        <a href="#funciones">
            Funciones
        </a>

        <a href="#ventas">
            Ventas
        </a>

        <a href="#inventario">
            Inventario
        </a>

        <a href="#sucursales">
            Sucursales
        </a>

        <a href="#nosotros">
            Nosotros
        </a>

        <a href="../../../../inicioSesion.php" class="btn-login">
            Iniciar sesión
        </a>

        <!-- BOTÓN MODO OSCURO -->
        <button id="darkModeBtn" class="dark-mode-btn" title="Cambiar modo">

            🌙

        </button>

    </nav>

</header>



<!-- =====================================================
     HERO
===================================================== -->

<section class="hero" id="inicio">


    <div class="hero-content">

        <div class="tag">
            🐾 SOFTWARE PARA VETERINARIAS
        </div>


        <h1>

            La administración
            de tu veterinaria,
            <span>más fácil.</span>

        </h1>


        <p>

            KION es un sistema de punto de venta e inventario
            diseñado para ayudarte a controlar las ventas,
            productos, usuarios y sucursales de tu negocio
            veterinario desde un solo lugar.

        </p>


        <div class="hero-buttons">

            <a href="#" class="btn-primary">
                Comenzar ahora
            </a>


            <a href="#funciones" class="btn-secondary">
                Conocer KION
            </a>

        </div>

    </div>



    <!-- MOCKUP DEL SISTEMA -->

    <div class="hero-visual">

        <div class="dashboard">


            <div class="dashboard-top">

                <div class="dashboard-title">
                    Panel de KION
                </div>


                <div class="dashboard-user">
                    🐾
                </div>

            </div>



            <div class="dashboard-stats">


                <div class="stat">

                    <small>
                        Ventas
                    </small>

                    <strong>
                        $24,580
                    </strong>

                </div>


                <div class="stat">

                    <small>
                        Productos
                    </small>

                    <strong>
                        328
                    </strong>

                </div>


                <div class="stat">

                    <small>
                        Sucursales
                    </small>

                    <strong>
                        4
                    </strong>

                </div>


            </div>



            <div class="dashboard-content">


                <div class="chart">

                    <h4>
                        Ventas recientes
                    </h4>


                    <div class="bars">

                        <div class="bar bar1"></div>

                        <div class="bar bar2"></div>

                        <div class="bar bar3"></div>

                        <div class="bar bar4"></div>

                        <div class="bar bar5"></div>

                    </div>

                </div>



                <div class="inventory">

                    <h4>
                        Inventario
                    </h4>


                    <div class="inventory-item">

                        <span>
                            Alimentos
                        </span>

                        <span class="available">
                            125
                        </span>

                    </div>


                    <div class="inventory-item">

                        <span>
                            Medicamentos
                        </span>

                        <span class="available">
                            48
                        </span>

                    </div>


                    <div class="inventory-item">

                        <span>
                            Accesorios
                        </span>

                        <span class="available">
                            73
                        </span>

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

    Una solución pensada para
    <strong>veterinarias que quieren tener el control de su negocio.</strong>

</section>



<!-- =====================================================
     FUNCIONES
===================================================== -->

<section class="features-section" id="funciones">


    <div class="section-label">
        TODO EN UN SOLO LUGAR
    </div>


    <h2 class="section-title">
        Todo lo que tu veterinaria necesita
    </h2>


    <p class="section-description">

        KION reúne las herramientas necesarias para
        administrar las operaciones de tu negocio
        de manera sencilla y organizada.

    </p>



    <div class="features-grid">


        <div class="feature-card">

            <div class="feature-icon">
                🛒
            </div>

            <h3>
                Punto de venta
            </h3>

            <p>
                Registra las ventas de alimentos,
                medicamentos, accesorios y productos
                veterinarios de manera rápida.
            </p>

        </div>



        <div class="feature-card">

            <div class="feature-icon">
                📦
            </div>

            <h3>
                Inventario
            </h3>

            <p>
                Mantén controladas las existencias
                de los productos de tu veterinaria.
            </p>

        </div>



        <div class="feature-card">

            <div class="feature-icon">
                🏪
            </div>

            <h3>
                Sucursales
            </h3>

            <p>
                Administra diferentes sucursales
                desde un mismo sistema.
            </p>

        </div>



        <div class="feature-card">

            <div class="feature-icon">
                👥
            </div>

            <h3>
                Usuarios
            </h3>

            <p>
                Gestiona los accesos y funciones
                de gerentes y cajeros.
            </p>

        </div>

    </div>

</section>



<!-- =====================================================
     VETERINARIAS
===================================================== -->

<section class="vet-section" id="nosotros">


    <div class="vet-text">

        <div class="section-label">
            PENSADO PARA VETERINARIAS
        </div>


        <h2>
            Tu veterinaria necesita más que una caja.
        </h2>


        <p>

            Administrar una veterinaria implica mucho más
            que realizar ventas. También necesitas controlar
            productos, existencias, sucursales y usuarios.

        </p>


        <ul class="check-list">

            <li>
                ✓ Control de productos veterinarios
            </li>

            <li>
                ✓ Registro de ventas
            </li>

            <li>
                ✓ Inventario actualizado
            </li>

            <li>
                ✓ Administración de sucursales
            </li>

            <li>
                ✓ Usuarios con diferentes funciones
            </li>

        </ul>

    </div>



    <div class="vet-visual">

        <div class="paw paw-one">
            🐾
        </div>

        <div class="paw paw-two">
            🐾
        </div>


        <div class="vet-card">


            <div class="vet-card-header">

                <div class="vet-icon">
                    🐶
                </div>

                <div>

                    <h3>
                        Productos veterinarios
                    </h3>

                </div>

            </div>


            <div class="vet-row">

                <span>
                    Alimentos
                </span>

                <strong>
                    125
                </strong>

            </div>


            <div class="vet-row">

                <span>
                    Medicamentos
                </span>

                <strong>
                    48
                </strong>

            </div>


            <div class="vet-row">

                <span>
                    Accesorios
                </span>

                <strong>
                    73
                </strong>

            </div>


            <div class="vet-row">

                <span>
                    Productos agropecuarios
                </span>

                <strong>
                    91
                </strong>

            </div>

        </div>

    </div>

</section>



<!-- =====================================================
     VENTAS
===================================================== -->

<section class="sales-section" id="ventas">


    <div class="sales-visual">

        <div class="sale-screen">

            <h3>
                Nueva venta
            </h3>


            <div class="sale-item">

                <span>
                    Alimento para perro
                </span>

                <strong>
                    $450
                </strong>

            </div>


            <div class="sale-item">

                <span>
                    Medicamento
                </span>

                <strong>
                    $280
                </strong>

            </div>


            <div class="sale-item">

                <span>
                    Accesorio
                </span>

                <strong>
                    $190
                </strong>

            </div>


            <div class="sale-total">

                <span>
                    Total
                </span>

                <span>
                    $920
                </span>

            </div>

        </div>

    </div>



    <div class="sales-text">

        <div class="section-label">
            PUNTO DE VENTA
        </div>


        <h2>
            Vende de forma rápida y organizada.
        </h2>


        <p>

            Registra cada operación desde el punto de venta
            y mantén la información de tu negocio organizada.

        </p>


        <ul class="check-list">

            <li>
                ✓ Registro de ventas
            </li>

            <li>
                ✓ Productos veterinarios
            </li>

            <li>
                ✓ Control por sucursal
            </li>

            <li>
                ✓ Actualización del inventario
            </li>

        </ul>

    </div>

</section>



<!-- =====================================================
     INVENTARIO
===================================================== -->

<section class="inventory-section" id="inventario">


    <div class="inventory-text">

        <div class="section-label">
            INVENTARIO
        </div>


        <h2>
            Mantén tus productos bajo control.
        </h2>


        <p>

            Con KION puedes consultar las existencias
            de los productos de tu veterinaria y mantener
            organizada la información de cada sucursal.

        </p>


        <ul class="check-list">

            <li>
                ✓ Consulta de existencias
            </li>

            <li>
                ✓ Organización por productos
            </li>

            <li>
                ✓ Control por sucursal
            </li>

            <li>
                ✓ Actualización después de una venta
            </li>

        </ul>

    </div>



    <div class="inventory-visual">

        <h3 style="margin-bottom:25px;">
            Inventario de productos
        </h3>


        <div class="inventory-table">


            <div class="table-header">

                <span>
                    Producto
                </span>

                <span>
                    Existencia
                </span>

                <span>
                    Estado
                </span>

            </div>


            <div class="table-row">

                <span>
                    Alimento
                </span>

                <span>
                    125
                </span>

                <span class="stock-good">
                    Disponible
                </span>

            </div>


            <div class="table-row">

                <span>
                    Medicamentos
                </span>

                <span>
                    48
                </span>

                <span class="stock-good">
                    Disponible
                </span>

            </div>


            <div class="table-row">

                <span>
                    Accesorios
                </span>

                <span>
                    73
                </span>

                <span class="stock-good">
                    Disponible
                </span>

            </div>


            <div class="table-row">

                <span>
                    Producto agropecuario
                </span>

                <span>
                    8
                </span>

                <span class="stock-low">
                    Bajo
                </span>

            </div>

        </div>

    </div>

</section>



<!-- =====================================================
     SUCURSALES
===================================================== -->

<section class="branches" id="sucursales">


    <div class="section-label">
        MULTI-SUCURSAL
    </div>


    <h2 class="section-title">
        Una sola plataforma para todas tus sucursales
    </h2>


    <p class="section-description">

        Centraliza la información y facilita la administración
        de cada punto de venta de tu negocio veterinario.

    </p>



    <div class="branches-grid">


        <div class="branch">

            <div class="branch-icon">
                🏪
            </div>

            <h3>
                Sucursal
            </h3>

            <p>
                Administra los productos y operaciones
                correspondientes a cada sucursal.
            </p>

        </div>



        <div class="branch">

            <div class="branch-icon">
                👨‍💼
            </div>

            <h3>
                Gerentes
            </h3>

            <p>
                Los responsables de cada sede pueden
                gestionar las operaciones de su sucursal.
            </p>

        </div>



        <div class="branch">

            <div class="branch-icon">
                👩‍💻
            </div>

            <h3>
                Cajeros
            </h3>

            <p>
                Realizan las ventas y consultan la
                información necesaria para trabajar.
            </p>

        </div>

    </div>

</section>



<!-- =====================================================
     COMO FUNCIONA
===================================================== -->

<section class="steps">


    <div class="section-label">
        SIMPLE Y ORGANIZADO
    </div>


    <h2 class="section-title">
        Comienza a utilizar KION
    </h2>


    <p class="section-description">

        Organiza las operaciones de tu veterinaria
        desde un solo sistema.

    </p>



    <div class="steps-grid">


        <div class="step">

            <div class="step-number">
                01
            </div>

            <h3>
                Registra
            </h3>

            <p>
                Agrega tus productos, sucursales
                y usuarios al sistema.
            </p>

        </div>



        <div class="step">

            <div class="step-number">
                02
            </div>

            <h3>
                Administra
            </h3>

            <p>
                Controla inventario, usuarios
                y operaciones de cada sucursal.
            </p>

        </div>



        <div class="step">

            <div class="step-number">
                03
            </div>

            <h3>
                Vende
            </h3>

            <p>
                Registra las ventas y mantén
                actualizado tu inventario.
            </p>

        </div>

    </div>

</section>



<!-- =====================================================
     BENEFICIOS
===================================================== -->

<section class="benefits">


    <div class="section-label">
        ¿POR QUÉ KION?
    </div>


    <h2 class="section-title">
        Menos complicaciones. Más control.
    </h2>



    <div class="benefits-grid">


        <div class="benefit">

            <strong>
                🐾 Especializado
            </strong>

            <p>
                Diseñado pensando en las necesidades
                de negocios veterinarios.
            </p>

        </div>



        <div class="benefit">

            <strong>
                ⚡ Sencillo
            </strong>

            <p>
                Una interfaz pensada para facilitar
                las operaciones diarias.
            </p>

        </div>



        <div class="benefit">

            <strong>
                📦 Organizado
            </strong>

            <p>
                Mantén tus productos e información
                organizada.
            </p>

        </div>



        <div class="benefit">

            <strong>
                🏪 Centralizado
            </strong>

            <p>
                Gestiona las diferentes sucursales
                desde un mismo sistema.
            </p>

        </div>

    </div>

</section>



<!-- =====================================================
     CTA
===================================================== -->

<section class="cta">


    <h2>
        Tu veterinaria necesita control.
    </h2>


    <p>
        Ventas, inventario y sucursales en un solo lugar.
    </p>


    <a href="#" class="btn-primary">
        Comenzar con KION
    </a>

</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer>


    <div class="logo">
        KI<span>O</span>N
    </div>


    <p>
        © 2026 KION · Punto de Venta e Inventario para Veterinarias
    </p>


</footer>



<!-- =====================================================
     JAVASCRIPT MODO OSCURO
===================================================== -->

<script>

    const darkModeBtn = document.getElementById("darkModeBtn");


    darkModeBtn.addEventListener("click", function () {

        document.body.classList.toggle("dark-mode");


        if (document.body.classList.contains("dark-mode")) {

            darkModeBtn.innerHTML = "☀️";

        } else {

            darkModeBtn.innerHTML = "🌙";

        }

    });

</script>


</body>
</html>