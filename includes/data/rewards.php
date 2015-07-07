<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 
 * @param type $post_campaign
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
        $this->rewards_list = $this->order_list($data_rewards);
        //$this->save();
    }
    
    private function order_list($list){
        //Sort the rewards by ascending amount
        function cmp($a, $b){
            return $diff = $a['amount'] - $b['amount'];
        }
        usort($list, "cmp");

        //Add a new ID to rewards who don't have one
        $max_id = 0;
        $without_id = array();
        while (list($key, $value) = each($list)) {
            if ($list[$key]['id']==''){
                $without_id[]= $key;
            } else {
                if (intval($list[$key]['id'])>$max_id){
                    $max_id = $list[$key]['id'];
                }
            }
        }
        foreach ($without_id as $element_need_id) {
            $max_id++;
            $list[$element_need_id]['id']=($max_id);
        }
        return $list;
    }
    /**
     * Get how many times a reward has been chosen
     * @param type $reward_id
     * @return type
     */
    public function get_reward_number_purchased($reward_id){
        $pos = $this->get_from_ID($reward_id);
        if($pos==-1){return "Error";}
        return intval($this->rewards_list[$pos]['bought']);
    }

    /**
     * Finds if a reward has a limited number of purchases
     * @param type $reward_id The reward ID to be checked
     * @return boolean TRUE If the reward is limited (integer >0), FALSE else
     */
    public function is_limited_reward($reward_id){
        $pos = $this->get_from_ID($reward_id);
        if($pos==-1){return "Error";}
        
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
        $pos = $this->get_from_ID($reward_id);
        if($pos==-1){return "Error";}
        
        if ($this->is_limited_reward($reward_id)){
            return (intval($this->rewards_list[$pos]['bought'])) < (intval($this->rewards_list[$pos]['limit']));
        } else {
            //If the reward is not limited, it is available.
            return TRUE;
        }

    }
    
    /**
     * Finds if a reward is still available to buy
     * @param type $reward_id The reward ID to be checked
     * @return boolean TRUE If success, FALSE else
     */
    public function buy_a_reward($reward_id){
        $pos = $this->get_from_ID($reward_id);
        if($pos==-1){return FALSE;}
        
        if ($this->is_available_reward($reward_id)){
            $this->rewards_list[$pos]['bought']=strval(intval($this->rewards_list[$pos]['bought'])+1);
            $this->save();
            return TRUE;
        } else {
            return FALSE;
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
     * @param type $reward_id The reward ID searched
     * @return type
     */
    public function get_from_ID($reward_ID){
        foreach ($this->rewards_list as $key => $value) {
            if ($value['id']==strval($reward_ID)){
                return $key;
            }
        }
        return -1;
    }
}
?>
