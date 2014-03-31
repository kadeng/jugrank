JugRank
=======

JugRank or JRank is a sports tournament system and iterative ranking algorithm invented by
Kai Londenberg in 2010.

I was annoyed about the usage of the so called [Swiss Tournament System](http://en.wikipedia.org/wiki/Swiss-system_tournament)
in a [Jugger](http://en.wikipedia.org/wiki/Jugger) Tournament where the number of participating teams was way too large to have every
team play against each other. 

I couldn't stand it and developed a vastly improved version of the Swiss System which used a more intelligent ranking / scoring
and team pairing algorithm.  

Then I did some Monte Carlo Simulations of virtual Tournaments with known ground-truths about actual team strengths. 
The JRank System outperformed all other systems it was tested against by a vast margin.

This repository contains the mathematical proof of convergence in the form of a small LaTex paper as well as a small Web-Application
written in PHP (comments in German, sorry) which allows to play and simulate tournaments using this system and to compare
simulated results to competing systems.

If you are fluent in German and want to see the working implementation and more friendly explanation of the system, please visit 
[The JugRank Page at hannover-jugger.de](http://www.hannover-jugger.de/joomla/index.php?option=com_wrapper&view=wrapper&Itemid=70)

#### Personal Note

Today I would probably do things a little different. I would probably use probabilistic modeling and bayesian inference to learn
team strengths during the course of the tournament, and try to maximize expected information gain for new tournament pairings.

But then, the approach I chose back in 2010 works pretty well.
