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
        $this->rewards_list = $this->order_list($data_rewards, $post);
        $this->save();
    }
    private function order_list($list, $post){
        //Sort the rewards by ascending amount
        function cmp($a, $b){
            return $diff = $a['amount'] - $b['amount'];
        }
        usort($list, "cmp");

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
}
?>
