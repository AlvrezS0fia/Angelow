<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guía de Tallas - ANGELOW</title>
    <link rel="shortcut icon" href="../../assets/imagenes/general/favico.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/guia_tallas.css">
</head>
<body>

<header>
    <a href="<?= APP_URL ?>/" class="logo"> 
    <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" class="logo-img">
    <div class="logo-text">
        <span>ANGELOW</span>
        <span>GUÍA DE TALLAS</span>
    </div>
</a>

<div class="icon-btn" onclick="window.location.href='<?= APP_URL ?>/'">
    <img src="<?= APP_URL ?>/assets/imagenes/general/volver.png" alt="Inicio" style="width:24px;">
</div>
</header>

<div class="terms-container">

    <div class="welcome-banner">
        <h2>GUÍA DE TALLAS</h2>
        <p>Encuentra la talla perfecta para tu hijo o hija con nuestras tablas detalladas y consejos prácticos</p>
    </div>

    <div class="terms-header">
        <h1>ENCUENTRA LA TALLA PERFECTA</h1>
        <p>Consulta nuestras tablas de medidas y aprende a tomar las medidas correctamente</p>
    </div>

    <div class="terms-nav">
        <a href="#bebes" class="nav-link active">Bebés</a>
        <a href="#ninas" class="nav-link">Niñas</a>
        <a href="#ninos" class="nav-link">Niños</a>
        <a href="#medidas" class="nav-link">Cómo Medir</a>
        <a href="#consejos" class="nav-link">Consejos</a>
        <a href="#equivalencias" class="nav-link">Equivalencias</a>
    </div>

    <div class="terms-content">

        <div class="highlight-box">
            <h3>Información Importante</h3>
            <p>Todas nuestras medidas están expresadas en centímetros. Las tallas están basadas en la edad promedio, pero cada niño es diferente. Te recomendamos siempre consultar las medidas específicas de altura, pecho, cintura y cadera para asegurar el mejor ajuste.</p>
            <p style="margin-top: 10px;"><strong>Nota:</strong> Si tu hijo o hija está entre dos tallas, te recomendamos elegir la talla mayor para mayor comodidad y durabilidad.</p>
        </div>

        <div class="term-section" id="bebes">
            <h2>TALLAS PARA BEBÉS (0-24 MESES)</h2>
            <p>Nuestras prendas para bebés están diseñadas pensando en su comodidad y facilidad de uso. Las tallas se basan en la edad y altura del bebé.</p>

            <div class="size-table-container">
                <table class="size-table">
                    <thead>
                        <tr><th>Talla</th><th>Edad</th><th>Altura (cm)</th><th>Peso (kg)</th><th>Pecho (cm)</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="size-label">0-3M</td><td>0-3 meses</td><td>50-62</td><td>3-6</td><td>40-43</td></tr>
                        <tr><td class="size-label">3-6M</td><td>3-6 meses</td><td>62-68</td><td>6-8</td><td>43-46</td></tr>
                        <tr><td class="size-label">6-9M</td><td>6-9 meses</td><td>68-74</td><td>8-9</td><td>46-48</td></tr>
                        <tr><td class="size-label">9-12M</td><td>9-12 meses</td><td>74-80</td><td>9-11</td><td>48-50</td></tr>
                        <tr><td class="size-label">12-18M</td><td>12-18 meses</td><td>80-86</td><td>11-12</td><td>50-52</td></tr>
                        <tr><td class="size-label">18-24M</td><td>18-24 meses</td><td>86-92</td><td>12-14</td><td>52-54</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="tip-box">
                <h4>Consejo para Bebés</h4>
                <p>Los bebés crecen muy rápido durante su primer año. Si tu bebé está cerca de pasar a la siguiente talla o quieres que la prenda dure más tiempo, considera comprar una talla más grande.</p>
            </div>
        </div>

        <div class="term-section" id="ninas">
            <h2>TALLAS PARA NIÑAS (2-12 AÑOS)</h2>
            <div class="size-table-container">
                <table class="size-table">
                    <thead>
                        <tr><th>Talla</th><th>Edad</th><th>Altura (cm)</th><th>Pecho (cm)</th><th>Cintura (cm)</th><th>Cadera (cm)</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="size-label">2T</td><td>2 años</td><td>92-98</td><td>54-56</td><td>51-53</td><td>56-58</td></tr>
                        <tr><td class="size-label">3T</td><td>3 años</td><td>98-104</td><td>56-58</td><td>53-55</td><td>58-61</td></tr>
                        <tr><td class="size-label">4T</td><td>4 años</td><td>104-110</td><td>58-60</td><td>55-57</td><td>61-64</td></tr>
                        <tr><td class="size-label">5</td><td>5 años</td><td>110-116</td><td>60-62</td><td>57-59</td><td>64-67</td></tr>
                        <tr><td class="size-label">6</td><td>6 años</td><td>116-122</td><td>62-65</td><td>59-61</td><td>67-70</td></tr>
                        <tr><td class="size-label">7</td><td>7 años</td><td>122-128</td><td>65-68</td><td>61-63</td><td>70-73</td></tr>
                        <tr><td class="size-label">8</td><td>8 años</td><td>128-134</td><td>68-71</td><td>63-66</td><td>73-77</td></tr>
                        <tr><td class="size-label">10</td><td>9-10 años</td><td>134-140</td><td>71-75</td><td>66-69</td><td>77-81</td></tr>
                        <tr><td class="size-label">12</td><td>11-12 años</td><td>140-152</td><td>75-80</td><td>69-72</td><td>81-86</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="term-section" id="ninos">
            <h2>TALLAS PARA NIÑOS (2-12 AÑOS)</h2>
            <div class="size-table-container">
                <table class="size-table">
                    <thead>
                        <tr><th>Talla</th><th>Edad</th><th>Altura (cm)</th><th>Pecho (cm)</th><th>Cintura (cm)</th><th>Cadera (cm)</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="size-label">2T</td><td>2 años</td><td>92-98</td><td>54-56</td><td>51-53</td><td>55-57</td></tr>
                        <tr><td class="size-label">3T</td><td>3 años</td><td>98-104</td><td>56-58</td><td>53-55</td><td>57-60</td></tr>
                        <tr><td class="size-label">4T</td><td>4 años</td><td>104-110</td><td>58-60</td><td>55-57</td><td>60-63</td></tr>
                        <tr><td class="size-label">5</td><td>5 años</td><td>110-116</td><td>60-63</td><td>57-59</td><td>63-66</td></tr>
                        <tr><td class="size-label">6</td><td>6 años</td><td>116-122</td><td>63-66</td><td>59-61</td><td>66-69</td></tr>
                        <tr><td class="size-label">7</td><td>7 años</td><td>122-128</td><td>66-69</td><td>61-64</td><td>69-72</td></tr>
                        <tr><td class="size-label">8</td><td>8 años</td><td>128-134</td><td>69-72</td><td>64-67</td><td>72-76</td></tr>
                        <tr><td class="size-label">10</td><td>9-10 años</td><td>134-140</td><td>72-76</td><td>67-70</td><td>76-80</td></tr>
                        <tr><td class="size-label">12</td><td>11-12 años</td><td>140-152</td><td>76-81</td><td>70-74</td><td>80-85</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="term-section" id="medidas">
            <h2>CÓMO TOMAR LAS MEDIDAS CORRECTAMENTE</h2>
            <div class="measurement-guide">
                <h3>Guía de Medición Paso a Paso</h3>
                <div class="measurement-item">
                    <h4>1. ALTURA</h4>
                    <p><strong>Cómo medir:</strong> Coloca al niño o niña descalzo, de pie, con la espalda completamente recta contra una pared. Mide desde el suelo hasta la parte superior de la cabeza.</p>
                </div>
                <div class="measurement-item">
                    <h4>2. PECHO</h4>
                    <p>Pasa la cinta métrica alrededor de la parte más amplia del pecho, justo debajo de las axilas.</p>
                </div>
                <div class="measurement-item">
                    <h4>3. CINTURA</h4>
                    <p>Mide alrededor de la parte más estrecha del torso (generalmente a la altura del ombligo).</p>
                </div>
                <div class="measurement-item">
                    <h4>4. CADERA</h4>
                    <p>Pasa la cinta alrededor de la parte más ancha de las caderas y glúteos.</p>
                </div>
            </div>
        </div>

        <div class="term-section" id="consejos">
            <h2>CONSEJOS PARA ELEGIR LA TALLA CORRECTA</h2>
            <div class="highlight-box">
                <ul>
                    <li>Compara con prendas actuales que le queden bien.</li>
                    <li>Considera la temporada y el crecimiento esperado.</li>
                    <li>Lee las reseñas de otros clientes sobre el ajuste.</li>
                    <li>Si está entre dos tallas, elige la mayor.</li>
                </ul>
            </div>
        </div>

        <div class="term-section" id="equivalencias">
            <h2>TABLA DE EQUIVALENCIAS INTERNACIONALES</h2>
            <div class="size-table-container">
                <table class="size-table">
                    <thead><tr><th>ANGELOW</th><th>USA</th><th>Europa</th><th>Edad</th></tr></thead>
                    <tbody>
                        <tr><td>2T</td><td>2T</td><td>92</td><td>2 años</td></tr>
                        <tr><td>3T</td><td>3T</td><td>98</td><td>3 años</td></tr>
                        <tr><td>4T</td><td>4T</td><td>104</td><td>4 años</td></tr>
                        <tr><td>5</td><td>5</td><td>110</td><td>5 años</td></tr>
                        <tr><td>6</td><td>6/6X</td><td>116</td><td>6 años</td></tr>
                        <tr><td>8</td><td>8</td><td>128</td><td>8 años</td></tr>
                        <tr><td>10</td><td>10</td><td>140</td><td>9-10 años</td></tr>
                        <tr><td>12</td><td>12</td><td>152</td><td>11-12 años</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<footer>
    <p>&copy; <span id="currentYear"></span> ANGELOW - Tienda Online de Ropa Infantil</p>
    <div class="footer-links">
        <a href="politica-privacidad.html">Política de Privacidad</a>
        <a href="terminos.html">Términos y Condiciones</a>
        <a href="pedidos-envios.html">Pedidos y Envíos</a>
        <a href="preguntas-frecuentes.html">Preguntas Frecuentes</a>
        <a href="contacto.html">Contacto</a>
    </div>
</footer>

<script src="<?= APP_URL ?>/assets/js/guia_tallas.js"></script>
</body>
</html>