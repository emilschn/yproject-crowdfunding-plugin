<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 
 * @param int The campaign ID
 * @return \ATCF_rewards
 */
function atcf_get_rewards( $post_campaign ){
    if(edd_has_variable_prices($post_campaign)){
        return new ATCF_rewards($post_campaign);
    } else { return null; }
}

class ATCF_rewards {
    /* array of sorted project rewards' */
    public $rewards_list;
    public $campaign_ID;
    
    function __construct( $post ) {
        $this->campaign_ID = $post;

        $data_rewards = edd_get_variable_prices($post);
        
        $is_safe = true;
        if (is_array($data_rewards)){
            foreach ($data_rewards as $value) {
                $is_safe = $is_safe && is_array($value);
            }
        } else { $is_safe = false; }
        
        if ($is_safe){
            $this->rewards_list = $this->order_list($data_rewards, $post);
        } else {
            $this->rewards_list = array();
        }
        $this->save();
    }
    
    static function cmp($a, $b){
        return $diff = $a['amount'] - $b['amount'];
    }
    
    private function order_list($list, $post){
        //Sort the rewards by ascending amount
        usort($list, array("ATCF_rewards","cmp"));
        foreach ($list as $key => $value) {
            //Add a new ID to rewards who don't have one
            if ($list[$key]['id']==''){
                $list[$key]['id']=$this->get_new_reward_ID($post);
            }
            
            //Set 0 bought to new rewards
            if ($list[$key]['bought']==''){
                $list[$key]['bought']=  strval(0);
            }
        }

        return $list;
    }
    
    private function get_new_reward_ID($post_id){
        $max_id = get_post_meta($post_id, 'campaign_rewards_id_counter', true);
        if($max_id==''){
            update_post_meta($post_id, 'campaign_rewards_id_counter', 1);
            return strval(0);
        } else {
            update_post_meta($post_id, 'campaign_rewards_id_counter', intval($max_id)+1);
            return $max_id;
        }
    }
    
    /**
     * Get how many times a reward has been chosen
     * @param type $reward_id
     * @return int
     */
    public function get_reward_number_purchased($reward_id){
       if($reward_id==-1){
            $count_no_reward = atcf_get_campaign($this->campaign_ID)->backers_count();
            foreach ($this->rewards_list as $key => $value) {
                $count_no_reward -= $value['bought'];
            }
            return $count_no_reward;
        }
        
        $pos = $this->get_pos_from_ID($reward_id);
        if($pos==-1){return null;}
        return intval($this->rewards_list[$pos]['bought']);
    }

    /**
     * Finds if a reward has a limited number of purchases
     * @param type $reward_id The reward ID to be checked
     * @return boolean TRUE If the reward is limited (integer >0), FALSE else
     */
    public function is_limited_reward($reward_id){
        $pos = $this->get_pos_from_ID($reward_id);
        if($pos==-1){return null;}
        
        $limit = $this->rewards_list[$pos]['limit'];
        if (is_numeric($limit)){
            return (is_int(intval($limit)) && intval($limit)>0);
        } else {
            return FALSE;
        }
    }
    
    /**
     * Finds if a reward is still available to buy
     * @param type $reward_id The reward ID to be checked
     * @return boolean TRUE If the reward is still available, FALSE else
     */
    public function is_available_reward($reward_id){
        $pos = $this->get_pos_from_ID($reward_id);
        if($pos==-1){return null;}
        
        if ($this->is_limited_reward($reward_id)){
            return (intval($this->rewards_list[$pos]['bought'])) < (intval($this->rewards_list[$pos]['limit']));
        } else {
            //If the reward is not limited, it is available.
            return TRUE;
        }

    }
    
    /**
     * Increments the "bought" data of a reward
     * @param type $reward_id The reward ID to be checked
     * @return boolean TRUE If success, FALSE else
     */
    public function buy_a_reward($reward_id){
        $pos = $this->get_pos_from_ID($reward_id);
        if($pos==-1){return FALSE;}
        
        $this->rewards_list[$pos]['bought']=strval(intval($this->rewards_list[$pos]['bought'])+1);
        $this->save();
        return TRUE;
    }
    
    /**
     * Get data associated to a reward by providing a reward ID
     * @param type $reward_id
     * @return type
     */
    public function get_reward_from_ID($reward_id){
        if($reward_id==-1){
            return array(
                'index' => '',
                'name' => 'Pas de contrepartie',
                'amount' =>'0',
                'limit' => '0',
                'bought' => $this->get_reward_number_purchased($reward_id),
                'id' => '-1'
            );
        }
        
        $pos = $this->get_pos_from_ID($reward_id);
        if($pos==-1){
            return null;
        } else {
            return $this->rewards_list[$pos];
        }
    }
    
    /**
     * Update the database with current object data
     */
    public function save(){
        $this->rewards_list = $this->order_list($this->rewards_list, $this->campaign_ID);
        $datasave = $this->rewards_list;
        update_post_meta($this->campaign_ID, 'edd_variable_prices', $datasave);
    }
    
    /**
     * Gets the position of a reward with the given ID
     * @param type $reward_ID The reward ID searched
     * @return type
     */
    private function get_pos_from_ID($reward_ID){
        foreach ($this->rewards_list as $key => $value) {
            if ($value['id']==strval($reward_ID)){
                return $key;
            }
        }
        return -1;
    }
    
    /**
     * Updates and save updates or new rewards
     * For each array : if the id is not provided, a new reward is added,
     * if the id is provided, the existing reward is modified with provided data
     * if the name is not provided or empty, the existing reward is deleted
     * @param array $array_new Array of the same pattern than $rewards_list
     */
    public function update_rewards_data($array_new){
        foreach ($array_new as $reward) {
            if (isset ($reward['id'])){
                if (($reward['name'])=='' || ($reward['amount'])==''){
                    //Suppression d'un existant
                    $nbelem = $this->get_pos_from_ID($reward['id']);
                    unset($this->rewards_list[$nbelem]);
                } else {
                    //Modification d'un existant
                    $nbelem = $this->get_pos_from_ID($reward['id']);
                    $this->rewards_list[$nbelem]['name']=$reward['name'];
                    $this->rewards_list[$nbelem]['amount']=strval($reward['amount']);
                    if($reward['limit']==0){
                        $this->rewards_list[$nbelem]['limit']='';
                    } else {
                        $this->rewards_list[$nbelem]['limit']=strval($reward['limit']);
                    }
                }
            } else if (($reward['name'])!='' && ($reward['amount'])!=''){
                //Ajout d'un nouveau
                $newReward = array();
                $newReward['name']=$reward['name'];
                $newReward['amount']=strval($reward['amount']);
                if($reward['limit']==0){
                    $newReward['limit']='';
                } else {
                    $newReward['limit']=strval($reward['limit']);
                }
                
                $this->rewards_list[]=$newReward;
            }
        }
        $this->save();
    }
}
?>
