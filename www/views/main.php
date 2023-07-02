<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>

<div id="app" class="pad-top">
  <p>Here you can see a variety of installations monitored with <a href="https://openenergymonitor.org/">OpenEnergyMonitor</a>, and compare detailed statistic to see how performance can vary.</p>
  <p>If you're monitoring a heat pump with <b>emoncms</b> and the <i>My Heat Pump</i> app, login with your emoncms.org login to add your system.</p>
  <p style="font-style: italic;">Join in with discussion of the results on the forums here: <a href="https://community.openenergymonitor.org/t/introducing-heatpumpmonitor-org-a-public-dashboard-of-heat-pump-performance/21885">Public dashboard of heat pumps</a>.</p>

  <div class="input-group mb-3" style="width:300px; float:right">
    <span class="input-group-text">Filter</span>
    <input id="search" name="query" v-model="filterKey" class="form-control">
  </div>

  <h4>{{ sortedNodes.length }} Systems</h4>

  <table width="100%" class="table-hpmon">
    <thead>
      <tr>
        <th class="header">&nbsp;</td>
        <th class="header" colspan="6">Property</th>
        <th class="header" colspan="4">Heating system</th>
        <th class="header" colspan="3">Annual Performance</th>
        <th class="header">&nbsp;</td>
      </tr>
      <tr>
        <th @click="sort('id', 'asc')" class="center">#</td>
        <!-- Property -->
        <th @click="sort('location', 'asc')">Location</th>
        <th @click="sort('property', 'asc')">Type</th>
        <th @click="sort('age', 'desc')">Built</th>
        <th @click="sort('floor_area', 'desc')" class="right">Floor Area</th>
        <th @click="sort('heat_demand', 'desc')" class="right">Heat Demand</th>
        <th @click="sort('insulation', 'asc')">Insulation</th>
        
        <!-- Heating System -->
        <th @click="sort('hp_model', 'asc')" class="border">Make / Model</th>
        <th @click="sort('hp_output', 'desc')" class="right">Output</th>
        <th @click="sort('hp_type', 'asc')">Source</th>
        <!--<th @click="sort('emitters', 'asc')">Emitters</th>-->
        
        <!-- Performance -->
        <th @click="sort('year_elec', 'desc')" class="border right">Electric</th>
        <th @click="sort('year_heat', 'desc')" class="right">Heat</th>
        <th @click="sort('year_cop', 'desc')" class="center">SCOP</th>
        <th class="nosort border">Charts</th>
      </tr>
    </thead>
    
    <tbody>
      <template v-for="n in sortedNodes"><tr v-bind:id="'row'+n.id" v-bind:class="hiliteClass(n)">
        <!-- Property -->
        <td class="toggle" onclick="toggle(this)" align="right" v-bind:title="'#'+n.id">&plusb;</td>
        <td>{{n.location}} {{isNew(n) ? '&#10024;' : ''}}</td> 
        <td>{{n.property}}</td>
        <td class="nowrap">{{n.age.replace(' to ', '-').trim()}}</td>
        <td class="nowrap" align="right">{{unit_dp(n.floor_area, 'm&sup2;')}}</td>
        <td class="nowrap" align="right">{{unit_dp(n.heat_demand, 'kWh')}}</td>
        <td v-bind:title="n.insulation">{{n.insulation.split(' ').slice(0, 2).join(' ')}}
           {{n.insulation.split(' ').length > 2 ? '&mldr;' : ''}}</td>
           
        <!-- Heating System -->
        <td v-bind:title="n.hp_model">{{n.hp_model.replace(/ \(.*\)/, ' &mldr;')}}</td>
        <td class="nowrap" align="right">{{unit_dp(n.hp_output, 'kW')}}</td>
        <td class="nowrap">{{n.hp_type.split(' ')[0]}}</td>
        <!--<td v-bind:title="n.emitters">
            {{n.emitters.split(' ').slice(0, 2).join(' ')}}
            {{n.emitters.split(' ').length > 2 ? '&mldr;' : ''}}</td>-->
        
        <!-- Performance -->
        <td v-bind:class="sinceClass(n) + ' nowrap  '" v-bind:title="sinceDate(n)" align="right">
           {{unit_dp(n.year_elec, 'kWh')}}
        </td>
        <td v-bind:class="sinceClass(n) + ' nowrap'" v-bind:title="sinceDate(n)" align="right">
           {{unit_dp(n.year_heat, 'kWh')}}
        </td>
        <td v-bind:class="sinceClass(n) + ' nowrap center'" v-bind:title="sinceDate(n)">
           {{n.year_cop > 0 ? n.year_cop.toFixed(1) : '-'}}</td>
        <td><a v-bind:href="n.url" target="_blank">Link &raquo;</a></td>
      </tr>
      
      <tr class="extra" style="display: none">
        <td class="extra">&nbsp;</td>
        <td colspan="14" class="extra">
          <b v-if="n.refrigerant">Refrigerant:</b> {{n.refrigerant}}
          <b v-if="n.flow_temp">Flow Temp:</b> {{n.flow_temp}}
          <b v-if="n.buffer">Buffer:</b> {{n.buffer}}
          <b v-if="n.zones">Zones:</b> {{n.zones}}
          <b v-if="n.controls">Controls:</b> {{n.controls}}
          <b v-if="n.freeze">Anti-Freeze:</b> {{n.freeze}}
          <b v-if="n.dhw">DHW:</b> {{n.dhw}}
          <b v-if="n.legionella">Legionella:</b> {{n.legionella}}
          <b v-if="n.notes">Notes:</b> {{n.notes}}
        </td>
      </tr></template>
    </tbody>
    
    <tfoot>
      <td colspan="1" class="footer"></td>
      <td colspan="6" class="footer">Recently added systems denoted by &#10024;</td>
      <td colspan="4" class="footer"></td>
      <td colspan="3" class="footer"><i>Incomplete data in grey</i></td>
      <td colspan="2" class="footer"></td>
    </tfoot>
  </table>
  
</div>
<script>var userid = <?php echo $userid; ?>;</script>
<script src="views/table.js?v=3.1"></script>