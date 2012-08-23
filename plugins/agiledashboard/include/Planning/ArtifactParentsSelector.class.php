<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Assuming that :
 * Release    -> Epic
 * `- Sprint  -> `- Story
 *
 * you want to create a story in a sprint S.
 *
 * Then the selector on story artifact creation will propose only
 * epics associated to S->release
 */
class Planning_ArtifactParentsSelector {

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory, PlanningFactory $planning_factory, Planning_MilestoneFactory $milestone_factory, Tracker_HierarchyFactory $hierarchy_factory) {
        $this->artifact_factory  = $artifact_factory;
        $this->planning_factory  = $planning_factory;
        $this->milestone_factory = $milestone_factory;
        $this->hierarchy_factory = $hierarchy_factory;
    }

    /**
     * @return array of Tracker_Artifact
     */
    public function getPossibleParents(Tracker $parent_tracker, Tracker_Artifact $source_artifact, User $user) {
        $milestone = $this->findNearestMilestoneWithBacklogTracker($parent_tracker, $source_artifact, $user);
        if ($milestone) {
            $linked_artifacts = $milestone->getLinkedArtifacts($user);
            array_walk($linked_artifacts, array($this, 'keepOnlyArtifactsBelongingToParentTracker'), $parent_tracker);
            return array_values(array_filter($linked_artifacts));
        }
        if ($source_artifact->getTracker() == $parent_tracker) {
            return array($source_artifact);
        }
        $parent_in_same_hierarchy = $this->getParentInSameHierarchy($parent_tracker, $source_artifact, $user);
        if ($parent_in_same_hierarchy) {
            return array($parent_in_same_hierarchy);
        }
        $sub_childs = $this->getSubChildrenBelongingToTracker($source_artifact, $parent_tracker, $user);
        if ($sub_childs) {
            return $sub_childs;
        }
        return array();
    }

    private function getSubChildrenBelongingToTracker(Tracker_Artifact $source_artifact, Tracker $expected_tracker, User $user) {
        $hierarchy = $this->getParentTrackersAndStopAtGivenTracker($expected_tracker, $source_artifact->getTracker());
        if ($hierarchy) {
            return $this->recursivelyFindChildrenBelongingToTracker($source_artifact, $expected_tracker, $user, $hierarchy);
        }
    }

    private function recursivelyFindChildrenBelongingToTracker(Tracker_Artifact $source_artifact, Tracker $expected_tracker, User $user, array $hierarchy) {
        $artifacts = array();
        $children = $source_artifact->getLinkedArtifactsOfHierarchy($user);
        if (isset($hierarchy[$source_artifact->getId()])) {
            array_walk($children, array($this, 'keepOnlyArtifactsBelongingToParentTracker'), $hierarchy[$source_artifact->getId()]);
            array_filter($children);
        }
        if ($children) {
            foreach ($children as $child) {
                if ($child->getTracker() == $expected_tracker) {
                    $artifacts[] = $child;
                } else {
                    $artifacts = array_merge($artifacts, $this->recursivelyFindChildrenBelongingToTracker($child, $expected_tracker, $user, $hierarchy));
                }
            }
        }
        return $artifacts;
    }

    private function getParentTrackersAndStopAtGivenTracker(Tracker $tracker, Tracker $stop) {
        $hierarchy = array();
        while (($parent = $this->hierarchy_factory->getParent($tracker)) && $parent != $stop) {
            $hierarchy[$parent->getId()] = $tracker;
            $tracker = $parent;
        }
        if ($parent == $stop) {
            $hierarchy[$stop->getId()] = $tracker;
            return $hierarchy;
        }
    }

    private function getParentInSameHierarchy(Tracker $expected_parent_tracker, Tracker_Artifact $source_artifact, User $user) {
        if ($source_artifact->getTracker() == $expected_parent_tracker) {
            return $source_artifact;
        } else {
            $parent = $source_artifact->getParent($user);
            if ($parent) {
                return $this->getParentInSameHierarchy($expected_parent_tracker, $parent, $user);
            }
        }
    }

    private function findNearestMilestoneWithBacklogTracker(Tracker $expected_backlog_tracker, Tracker_Artifact $source_artifact, User $user) {
        $planning = $this->planning_factory->getPlanningByPlanningTracker($source_artifact->getTracker());
        if ($planning && $planning->getBacklogTracker() == $expected_backlog_tracker) {
            return  $this->milestone_factory->getMilestoneFromArtifactWithPlannedArtifacts($source_artifact);
        } else {
            $parent = $source_artifact->getParent($user);
            if ($parent) {
                return $this->findNearestMilestoneWithBacklogTracker($expected_backlog_tracker, $parent, $user);
            }
        }
    }

    private function keepOnlyArtifactsBelongingToParentTracker(&$artifact, $key, $parent_tracker) {
        if ($artifact->getTracker() != $parent_tracker) {
            $artifact = null;
        }
    }
}
?>
