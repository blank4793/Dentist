<?php
// Include this at the top of patient-form.php
?>
<div class="dental-chart-section">
    <div class="chart-container">
        <!-- Adult Teeth Chart -->
        <div class="teeth-chart adult">
            <!-- Upper Teeth -->
            <div class="teeth-row upper">
                <!-- Upper Right Molars -->
                <div class="tooth" data-tooth="18">
                    <div class="tooth-shape">
                        <img src="images/18.png" alt="Tooth 18" class="tooth-img">
                        <span>18</span>
                    </div>
                </div>

                <div class="tooth" data-tooth="17">
                    <div class="tooth-shape">
                        <svg viewBox="0 0 40 40">
                            <path class="molar" d="M8,8 L32,8 L32,32 L8,32 Z M12,12 L28,12 L28,28 L12,28 Z M16,16 L24,16 L24,24 L16,24 Z"/>
                        </svg>
                        <span>17</span>
                    </div>
                </div>

                <div class="tooth" data-tooth="16">
                    <div class="tooth-shape">
                        <svg viewBox="0 0 40 40">
                            <path class="molar" d="M8,8 L32,8 L32,32 L8,32 Z M12,12 L28,12 L28,28 L12,28 Z M16,16 L24,16 L24,24 L16,24 Z"/>
                        </svg>
                        <span>16</span>
                    </div>
                </div>

                <!-- Upper Right Premolars -->
                <div class="tooth" data-tooth="15">
                    <div class="tooth-shape">
                        <svg viewBox="0 0 40 40">
                            <!-- Two-cusped premolar shape -->
                            <path class="premolar" d="M15,10 L25,10 L30,15 L30,30 L25,35 L15,35 L10,30 L10,15 Z
                                                    M17,15 L23,15 M17,22 L23,22"/>
                        </svg>
                        <span>15</span>
                    </div>
                </div>

                <!-- Upper Right Canine -->
                <div class="tooth" data-tooth="13">
                    <div class="tooth-shape">
                        <svg viewBox="0 0 40 40">
                            <!-- Pointed canine shape -->
                            <path class="canine" d="M20,5 L30,15 L25,35 L15,35 L10,15 Z"/>
                        </svg>
                        <span>13</span>
                    </div>
                </div>

                <!-- Upper Right Incisors -->
                <div class="tooth" data-tooth="11">
                    <div class="tooth-shape">
                        <svg viewBox="0 0 40 40">
                            <!-- Flat-edged incisor shape -->
                            <path class="incisor" d="M12,10 L28,10 L25,35 L15,35 Z"/>
                        </svg>
                        <span>11</span>
                    </div>
                </div>

                <!-- Upper Left -->
                <div class="tooth" data-tooth="21"><div class="tooth-shape">21</div></div>
                <div class="tooth" data-tooth="22"><div class="tooth-shape">22</div></div>
                <div class="tooth" data-tooth="23"><div class="tooth-shape">23</div></div>
                <div class="tooth" data-tooth="24"><div class="tooth-shape">24</div></div>
                <div class="tooth" data-tooth="25"><div class="tooth-shape">25</div></div>
                <div class="tooth" data-tooth="26"><div class="tooth-shape">26</div></div>
                <div class="tooth" data-tooth="27"><div class="tooth-shape">27</div></div>
                <div class="tooth" data-tooth="28"><div class="tooth-shape">28</div></div>
            </div>

            <!-- Lower Teeth -->
            <div class="teeth-row lower">
                <!-- Lower Right -->
                <div class="tooth" data-tooth="48"><div class="tooth-shape">48</div></div>
                <div class="tooth" data-tooth="47"><div class="tooth-shape">47</div></div>
                <div class="tooth" data-tooth="46"><div class="tooth-shape">46</div></div>
                <div class="tooth" data-tooth="45"><div class="tooth-shape">45</div></div>
                <div class="tooth" data-tooth="44"><div class="tooth-shape">44</div></div>
                <div class="tooth" data-tooth="43"><div class="tooth-shape">43</div></div>
                <div class="tooth" data-tooth="42"><div class="tooth-shape">42</div></div>
                <div class="tooth" data-tooth="41"><div class="tooth-shape">41</div></div>
                
                <!-- Lower Left -->
                <div class="tooth" data-tooth="31"><div class="tooth-shape">31</div></div>
                <div class="tooth" data-tooth="32"><div class="tooth-shape">32</div></div>
                <div class="tooth" data-tooth="33"><div class="tooth-shape">33</div></div>
                <div class="tooth" data-tooth="34"><div class="tooth-shape">34</div></div>
                <div class="tooth" data-tooth="35"><div class="tooth-shape">35</div></div>
                <div class="tooth" data-tooth="36"><div class="tooth-shape">36</div></div>
                <div class="tooth" data-tooth="37"><div class="tooth-shape">37</div></div>
                <div class="tooth" data-tooth="38"><div class="tooth-shape">38</div></div>
            </div>
        </div>

        <!-- Selected Teeth List -->
        <div class="selected-teeth-info">
            <h3>Selected Teeth</h3>
            <div id="selectedTeethList"></div>
        </div>
    </div>
</div> 
</div> 