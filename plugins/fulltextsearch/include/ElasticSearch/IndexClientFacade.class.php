<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */


class ElasticSearch_IndexClientFacade extends ElasticSearch_ClientFacade implements FullTextSearch_IIndexDocuments {
    
    /**
     * @see FullTextSearch_IIndexDocuments::index
     */
    public function index(array $document, Docman_Item $item) {
        $this->client->setType($item->getGroupId());

        $this->client->index($document, $item->getId());
    }

    /**
     * @see FullTextSearch_IIndexDocuments::delete
     */
    public function delete(Docman_Item $item) {
        $this->client->setType($item->getGroupId());


        $this->client->delete($item->getId());
    }

    /**
     * @see FullTextSearch_IIndexDocuments::update
     */
    public function update(Docman_Item $item, $data) {
        $this->client->setType($item->getGroupId());

        $this->client->request($item->getId().'/_update', 'POST', $data);
    }

    /**
     * make a parameter with name $name and value $value
     * then append it to current_data as script and var
     */
    public function appendSetterData(array $current_data, $name, $value) {
        $current_data['script']       .= "ctx._source.$name = $name;";
        $current_data['params'][$name] = $value;
        return $current_data;
    }

    /**
     * Return the base to build a setter data
     *
     * @return array
     */
    public function initializeSetterData() {
        return array(
            'script' => '',
            'params' => array()
        );
    }

    public function getProjectMapping($project_id) {
        return $this->client->request($project_id.'/_mapping', 'GET', array(), true);
    }

    public function initializeProjectMapping($project_id, array $mapping_data) {
        try {
            $this->client->request($project_id.'/_mapping', 'PUT', $mapping_data, true);
        } catch (Exception $ex) {}
    }
}